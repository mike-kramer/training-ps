# План деплоя: Ubuntu + Docker → GitHub Actions

Учебный **Training Payment System** (Laravel + Vue/Vite + PostgreSQL + Redis).

**Общие правила на всех этапах:**
- свои образы **не публикуем в Docker Hub**;
- базовые `FROM` (`debian`, `nginx`, `postgres`, `redis`, `node`, `certbot`) при сборке/pull всё равно берутся из registry — это нормально;
- секреты только на сервере (`server-docker/.env`, `server-docker/application.env`) / в GitHub Secrets, не в git.

Документ: ручной деплой (фаза A, уже реализована в репо) → карта перехода на бесплатный GitHub Actions.

**Выбранный вариант фазы A:** multistage-образ с копией кода → при старте sync в **named volume** `app_code` (общий для nginx и php). Bind-mount дерева `application/` в runtime **нет**.

---

## 0. Две папки Docker: `docker/` и `server-docker/`

| | `docker/` | `server-docker/` |
|--|-----------|------------------|
| Назначение | Локальная разработка | Боевой / staging стек |
| Где живёт | В git | В git как шаблон; на сервере рядом с клоном репо (нужен `application/` как **build context**) |
| Compose | bind-mount кода, Xdebug, Mailhog, порты БД наружу, `postgres/init-schema.sql` для тестовой схемы | без Xdebug/Mailhog; БД/Redis не публикуются; Horizon + `scheduler`; Certbot; **нет** init-schema |
| Код в runtime | `../application` с хоста | только из образа → volume `app_code` |

`docker/` **не ломаем** — оставляем для dev. Тестовая схема Postgres (`init-schema.sql`) остаётся **только** в `docker/`.

### Раскладка на сервере

```text
/var/www/html/training-ps/             # клон репозитория
├── application/                       # только контекст docker build (+ git pull)
└── server-docker/
    ├── docker-compose.yml
    ├── .env                           # DOMAIN, пароли БД/Redis, NGINX_TEMPLATE, …
    ├── .env.example
    ├── application.env                # Laravel .env (mount в контейнер)
    ├── application.env.example
    ├── php/                           # multistage Dockerfile + entrypoint + ini
    ├── nginx/templates/               # http-only и TLS (envsubst ${DOMAIN})
    └── certbot/                       # init-cert.sh, renew.sh
```

Named volumes (Docker, не в git):

| Volume | Назначение |
|--------|------------|
| `app_code` | дерево приложения `/var/www/html` для nginx + php + horizon + scheduler |
| `app_storage` | `/var/www/html/storage` (uploads, логи, cache sessions) — переживает пересборку образа |
| `postgres_data` / `redis_data` | данные БД |
| `certbot_www` / `certbot_certs` | ACME webroot и сертификаты Let’s Encrypt |

С хоста в PHP монтируется **только** `./application.env` → `/var/www/html/.env` (не всё дерево `application/`).

### Как устроен runtime (фаза A)

```text
docker compose build
        │
        ▼
multistage image training-ps-php:prod
  /opt/app-src  ← frontend + vendor + app
        │
        ▼  entrypoint: rsync (исключая .env и storage/)
named volume app_code  ←── nginx (ro), php, horizon, scheduler
named volume app_storage ←── mount на .../storage
./application.env        ←── mount на .../.env
```

Обновление кода = `git pull` → `docker compose build php` → `up -d` (entrypoint заново наполняет `app_code`).

---

## 1. Что есть сейчас (dev в `docker/`)

| Сервис   | Образ / сборка                    | Заметки |
|----------|-----------------------------------|---------|
| `nginx`  | `nginx:1.27-alpine`               | bind-mount `../application` |
| `php`    | `docker/php/Dockerfile`           | PHP 8.5 FPM + Xdebug, bind-mount |
| `db`     | `postgres:17`                     | порт наружу, `init-schema.sql` → `test_schema` |
| `redis`  | `redis:8.8.1-alpine`              | порт наружу |
| `mailhog`| `mailhog/mailhog`                 | локальная почта |

Фронт в Docker-стеке не собирается. Scheduler и Horizon на проде — отдельные сервисы в `server-docker/`.

---

## 2. Фаза A — ручной деплой (реализовано в `server-docker/`)

### 2.1. Отличия от `docker/`

| Тема | `docker/` (dev) | `server-docker/` (фаза A) |
|------|-----------------|---------------------------|
| Код в runtime | bind-mount `../application` | образ → rsync → volume `app_code` |
| Сборка | нет | multistage: Node (Vite) → Composer `--no-dev` → PHP-FPM |
| `application/` на хосте | живой код | только **build context** |
| Секреты Laravel | `application/.env` | `server-docker/application.env` (bind-mount) |
| Порты БД/Redis | открыты | не публиковать |
| init-schema / test_schema | да | **нет** (схему дают миграции) |
| Nginx | dev-порт | `80`/`443`, шаблоны + `${DOMAIN}` |
| Certbot | нет | profile `certbot`, init + renew |
| Mailhog / Xdebug / PUID | есть | нет |
| Queue / scheduler | нет | те же image/volumes, другой `command` |
| Opcache | `validate_timestamps=1` | `validate_timestamps=0` → нужен recreate php после деплоя |

### 2.2. Multistage Dockerfile (кратко)

1. **frontend** — `npm ci` → `npm run build` → `public/build`.
2. **vendor** — два `composer install` для кэша слоёв Docker (сначала по lock-файлам, потом после `COPY` исходников + dump-autoload).
3. **runtime** — PHP 8.5 FPM без Xdebug/Node; код в `/opt/app-src`; entrypoint синкает в `/var/www/html`, затем `key:generate` / `storage:link` / `migrate --force` (после healthcheck Postgres; на этапе `docker build` БД недоступна — это не `Dockerfile RUN`).

Контекст сборки: **корень репозитория** (`context: ..`, `dockerfile: server-docker/php/Dockerfile`).  
`.dockerignore` в корне репо отсекает `node_modules`, `vendor`, тесты и т.п.

### 2.3. Laravel `application.env`

Шаблон: `server-docker/application.env.example` → `application.env` на сервере.

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=pgsql
DB_HOST=db
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

REDIS_HOST=redis
REDIS_PASSWORD=...

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
SANCTUM_STATEFUL_DOMAINS=your-domain.example
```

Один раз ничего вручную для ключа/storage/миграций не нужно — entrypoint:  
`key:generate` (без `--force`, существующий `APP_KEY` не трогает), `storage:link`, `migrate --force`.  
Кэши config/route/view не используем (демопроект).

### 2.4. Схема сервисов

```text
Internet → nginx:80 (ACME + redirect) / 443 (TLS)
              ↓  volume app_code (+ app_storage)
            php-fpm
              ↓
         postgres, redis   (только внутренняя сеть)

horizon / scheduler — тот же image (`horizon` / `schedule:work`)
certbot — profile, one-shot / renew
```

### 2.5. HTTPS: Certbot в Docker (начальная настройка)

Предпосылки: DNS A/AAAA на IP сервера; UFW `80`/`443`; в `.env` — `DOMAIN`, `CERTBOT_EMAIL`.

Порядок:

1. `NGINX_TEMPLATE=default.http-only.conf.template` — HTTP + `/.well-known/acme-challenge/` на `certbot_www`.
2. `./certbot/init-cert.sh` (webroot).
3. `NGINX_TEMPLATE=default.conf.template` → `docker compose up -d nginx`.
4. В `application.env`: `APP_URL=https://...` (перезапуск php подхватит новый `.env` с mount).
5. Cron: `certbot/renew.sh` (`certbot renew` + `nginx -s reload`).

Образы `certbot/certbot` / `nginx` — чужие, свои не пушим.

### 2.6. Порядок на чистом Ubuntu

**0.** Docker Compose v2; UFW `22`/`80`/`443`; DNS на домен.

**1.** Клон репозитория целиком (нужны и `application/`, и `server-docker/`):

```bash
sudo mkdir -p /var/www/html && sudo chown "$USER:$USER" /var/www/html
git clone <URL> /var/www/html/training-ps
cd /var/www/html/training-ps/server-docker
```

**2.** Env:

```bash
cp .env.example .env                 # DOMAIN, CERTBOT_EMAIL, пароли, NGINX_TEMPLATE
cp application.env.example application.env
```

**3.** Сборка и старт (HTTP):

```bash
docker compose build
docker compose up -d
```

**4.** Сертификат: `./certbot/init-cert.sh` → переключить `NGINX_TEMPLATE` → `up -d nginx`.

**5.** После TLS обновить `APP_URL` в `application.env` и пересоздать php/horizon/scheduler. Ключ, `storage:link` и миграции — в entrypoint (см. README).

**6.** Cron для `renew.sh`; проверка `renew --dry-run`.

**Обновление:**

```bash
cd /var/www/html/training-ps && git pull
cd server-docker
docker compose build php
docker compose up -d php horizon scheduler nginx
# migrate --force runs in the entrypoint on php start (RUN_SETUP=1)
```

Certbot при обычном релизе не трогать.

---

## 3. Фаза B — GitHub Actions (бесплатно, без Docker Hub)

Текущая модель фазы A — **код в образе + named volumes**. Фаза B должна к этому стыковаться, а не возвращать bind-mount `application/` без явного решения сменить архитектуру.

### 3.1. Рекомендуемый путь (согласован с фазой A)

Actions **не** rsync’ит дерево в runtime-volume. Вместо этого по SSH на сервере:

```text
git pull
→ docker compose build php          # multistage на сервере
→ docker compose up -d …
→ artisan migrate --force (entrypoint)
```

| | Фаза A | Фаза B (рекомендация) |
|--|--------|------------------------|
| Где Vite / Composer | multistage на сервере | то же, но **триггер** с GitHub (SSH) |
| Multistage | да | пока **да** (на сервере) |
| Доставка кода | ручной `git pull` + build | Actions: SSH → `git pull` + build |
| Runtime | `app_code` / `app_storage` | без изменений |
| `application.env` | только на сервере | CI **не** затирает |

Так мы остаёмся без своего Docker Hub/GHCR и без смены модели томов.

### 3.2. Альтернатива позже (если уйдём от multistage на сервере)

Только если сознательно меняем архитектуру:

- **B2:** тонкий PHP-образ + rsync файлов в volume/`application` (как в ранних набросках плана) — тогда multistage на сервере не нужен, сборка Vite/Composer на runner.
- **B3:** сборка image на runner и push в **GHCR** (не Docker Hub) → на сервере `pull` + `up` — минуты/storage GHCR, зато нет тяжёлого build на VPS.

Пока цель — простота и free tier: остаёмся на **§3.1**.

### 3.3. Free tier

- Public repo: минуты Actions обычно без жёсткого лимита на стандартных runner’ах.
- Private: порядка **2000 мин/месяц** — деплой 1–2 раза в день обычно хватает.
- Экономия: деплой по тегу / `workflow_dispatch`; не гонять matrix; path-filters; не собирать лишнее на каждый README.

Тяжёлая часть (multistage) выполняется на **сервере**, минуты GitHub уходят в основном на checkout + SSH — это дёшево. Если build на VPS слишком долгий — смотреть B3 (GHCR).

### 3.4. Карта пайплайна (рекомендация §3.1)

```text
push/tag/workflow_dispatch
        │
        ▼
┌──────────── GitHub-hosted runner ────────────┐
│ checkout (опционально: lint/test)            │
│ SSH → git pull && compose up --build         │
└──────────────────────┬───────────────────────┘
                       ▼
┌──────────── сервер ──────────────────────────┐
│ git pull                                     │
│ cd server-docker                             │
│ docker compose build php                     │
│ docker compose up -d php horizon scheduler nginx│
│ artisan migrate --force (entrypoint on start) │
└──────────────────────────────────────────────┘
```

Секреты: `SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`.  
Команды деплоя лежат в `.github/workflows/deploy.yml` и выполняются по SSH. Отдельный скрипт на сервер заранее не кладётся.

### 3.5. Поэтапное внедрение Actions

1. Стабилизировать ручной прогон фазы A (см. README).
2. Workflow только `workflow_dispatch` → SSH → `git pull` и `docker compose up -d --build`.
3. Опционально: `ci.yml` на PR (pint/phpunit) без деплоя.
4. Автодеплой с `main` или по тегу `v*`.
5. При нехватке ресурсов VPS на build — оценить GHCR (B3), не Docker Hub.

Откат: предыдущий git-тег + `build` / `up`; локально перед сборкой можно `docker tag training-ps-php:prod training-ps-php:prod-prev`.

### 3.6. Чего не делать

- Не пушить свои образы в Docker Hub.
- Не затирать `application.env` с CI.
- Не смешивать «тихо» bind-mount `application/` с моделью образа без явного рефакторинга.
- Не гонять полный matrix PHP на каждый push.

---

## 4. Чеклисты

### Фаза A (в репозитории уже есть шаблон)

- [x] `server-docker/` multistage + compose + nginx templates + certbot scripts
- [x] Volumes `app_code` / `app_storage`; без bind-mount кода; без prod init-schema
- [x] Queue + scheduler; без Mailhog/Xdebug; БД/Redis без publish
- [ ] Прогон на чистом Ubuntu по §2.6
- [ ] DNS + `init-cert.sh` + TLS template + cron `renew.sh`
- [ ] Заполненные `.env` / `application.env` только на сервере

### Фаза B

- [ ] SSH deploy-пользователь + GitHub Secrets
- [ ] SSH-команды в workflow `deploy.yml` + `workflow_dispatch`
- [ ] Автодеплой по договорённости (ветка/тег)
- [ ] Оценка времени `docker compose build` на сервере

---

## 5. Риски

| Риск | Закрытие |
|------|----------|
| Opcache отдаёт старый код | recreate php после деплоя (`validate_timestamps=0`) |
| Потеря uploads при пересборке | volume `app_storage` |
| Затирание `.env` при rsync в entrypoint | exclude `.env`; секреты только через mount `application.env` |
| `public/hot` на проде | не копировать; `rm` в Dockerfile frontend stage |
| Certbot fail | DNS, UFW 80, http-only template до сертификата |
| Nginx без сертификата | сначала `default.http-only.conf.template` |
| Протухший сертификат | cron `renew.sh` + `renew --dry-run` |
| Долгий build на слабом VPS | кэш BuildKit; позже GHCR (B3) |
| Исчерпание минут Actions | лёгкий runner (SSH-only deploy), деплой по тегу |

---

## 6. Связь с файлами репозитория

| Путь | Роль |
|------|------|
| `docker/*` | Только разработка |
| `docker/postgres/init-schema.sql` | Только dev (test schema); в prod нет |
| `server-docker/*` | Прод-стек фазы A |
| `server-docker/php/Dockerfile` | Multistage: frontend → vendor → runtime |
| `server-docker/php/docker-entrypoint.sh` | rsync `/opt/app-src` → `app_code` |
| `server-docker/application.env.example` | Шаблон Laravel env на сервере |
| `.dockerignore` | Контекст сборки с корня репо |
| `application/package.json` → `build` | Stage frontend |
| `application/bootstrap/app.php` | Scheduler → сервис `scheduler` |

---

## 7. Краткая формула

1. **Фаза A:** код в multistage-образе → volume `app_code` (+ `app_storage`); с хоста только `application.env`; Certbot в Docker в начальной настройке.
2. **Фаза B:** Actions по SSH запускает на сервере `git pull` + `docker compose build` + `up`; миграции — в entrypoint. Модель томов не ломаем. Отдельный путь «rsync файлов / тонкий образ» — только при явном смене архитектуры.

Подробности команд: `server-docker/README.md`.
