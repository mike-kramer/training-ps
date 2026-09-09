#!/usr/bin/env bash
# Renew Let's Encrypt certificates and reload nginx.
set -euo pipefail

cd "$(dirname "$0")/.."

docker compose --profile certbot run --rm certbot renew
docker compose exec nginx nginx -s reload

echo "Renewal finished ($(date -Is))."
