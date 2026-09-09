#!/usr/bin/env bash
# First Let's Encrypt certificate (webroot). Run from server-docker/ after HTTP nginx is up.
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f .env ]]; then
  echo "Missing server-docker/.env — copy from .env.example first." >&2
  exit 1
fi

set -a
# shellcheck disable=SC1091
source ./.env
set +a

: "${DOMAIN:?DOMAIN is required in .env}"
: "${CERTBOT_EMAIL:?CERTBOT_EMAIL is required in .env}"

echo "Requesting certificate for ${DOMAIN} ..."
docker compose --profile certbot run --rm certbot certonly \
  --webroot -w /var/www/certbot \
  -d "${DOMAIN}" \
  --email "${CERTBOT_EMAIL}" \
  --agree-tos \
  --no-eff-email \
  --non-interactive

echo
echo "Certificate issued. Next steps:"
echo "  1. Set NGINX_TEMPLATE=default.conf.template in .env"
echo "  2. docker compose up -d nginx"
echo "  3. Set APP_URL=https://${DOMAIN} (and Sanctum/session domain) in application.env"
echo "  4. Install renew cron: see certbot/renew.sh"
