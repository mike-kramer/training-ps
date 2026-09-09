# Production Docker stack (`server-docker`)

Multistage PHP image (Node → Composer → PHP-FPM). Application code is **baked into the image** and synced on start into a shared named volume (`app_code`) for nginx + PHP. Host tree `application/` is only the Docker **build context**, not a runtime bind-mount. Certbot runs via Docker. Local development stays in `../docker/`.

## Layout

```text
server-docker/
├── docker-compose.yml
├── .env.example                 # copy → .env
├── application.env.example      # copy → application.env (Laravel secrets)
├── php/                         # multistage Dockerfile + FPM config
├── nginx/templates/             # http-only and TLS (envsubst DOMAIN)
└── certbot/                     # init-cert.sh, renew.sh
```

Build context is the **repository root** (`..`). The primary `php` service sets `RUN_SETUP=1`; `horizon` / `scheduler` use the same image with `RUN_SETUP=0` and wait for `php` **healthy** (volume sync + migrations finished), not merely started.

On start of the primary container the entrypoint:

1. rsync `/opt/app-src` → volume `app_code` (keeps `.env` and `storage/`);
2. `php artisan key:generate` only if `APP_KEY` is still empty;
3. `php artisan storage:link`;
4. `php artisan migrate --force` (after Compose `db` healthcheck).

Persistent uploads/logs: volume `app_storage`. Host secrets: `./application.env` → `/var/www/html/.env`.

These artisan steps are **not** in the Dockerfile build — Postgres is not available at image build time.

## First deploy

1. DNS A/AAAA for `DOMAIN` → server IP. Open firewall ports `22`, `80`, `443`.
2. Clone/copy the repo so `server-docker/` sits next to `application/` (needed for `docker compose build`).
3. Configure env files:

   ```bash
   cd server-docker
   cp .env.example .env
   # edit DOMAIN, CERTBOT_EMAIL, passwords

   cp application.env.example application.env
   # same DOMAIN/passwords; APP_URL may start as http:// until TLS is on
   ```

4. Build and start (HTTP + ACME location) — key, storage link, and migrations run automatically on php start (`RUN_SETUP=1`):

   ```bash
   docker compose build
   docker compose up -d
   ```

5. Issue certificate:

   ```bash
   chmod +x certbot/*.sh
   ./certbot/init-cert.sh
   ```

6. Switch nginx to TLS:

   ```bash
   # in .env:
   # NGINX_TEMPLATE=default.conf.template
   docker compose up -d nginx
   ```

   After TLS, set `APP_URL=https://$DOMAIN` (and Sanctum/session domain) in `application.env`, then recreate php so it reloads env (`docker compose up -d php horizon scheduler`).

7. Certificate renewal (host cron):

   ```cron
   0 3 * * * /opt/training-payment-system/server-docker/certbot/renew.sh >> /var/log/certbot-renew.log 2>&1
   ```

   Dry-run: `docker compose --profile certbot run --rm certbot renew --dry-run`

## Updates

```bash
cd /path/to/repo && git pull
cd server-docker
docker compose build php
docker compose up -d php horizon scheduler nginx
docker compose rm -f queue 2>/dev/null || true
```

Rebuild + recreate runs entrypoint sync, `key:generate`, `storage:link`, and `migrate --force`. Opcache has `validate_timestamps=0` — recreating PHP containers is required after a code deploy.

## Notes

- Postgres and Redis ports are **not** published.
- No Mailhog / Xdebug.
- No `config:cache` / `route:cache` / `view:cache` — demo traffic does not need them.
- Images stay local (`training-ps-php:prod`); base images are pulled when building/starting.
- Queues: Laravel Horizon (`php artisan horizon`) with `QUEUE_CONNECTION=redis`. Dashboard: `/horizon` (authenticated users).
- Scheduler: `schedule:work` (includes `app:cancel-old-payments` and `horizon:snapshot`).
- Local development (`docker/`) stays on database queues — Horizon is production-only.
- Do not commit `application.env` or `.env`.
