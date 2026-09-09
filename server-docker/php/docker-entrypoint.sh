#!/bin/sh
set -e

# Sync + Laravel setup run only on the primary app container (RUN_SETUP=1).
# queue/scheduler use the same image with RUN_SETUP=0 and depend_on php, so
# app_code is already populated. Do not put this flag in the Dockerfile — one
# image is shared; differentiate via compose environment.
if [ "${RUN_SETUP:-0}" = "1" ]; then
    # Clear readiness so dependents (horizon/scheduler healthcheck) wait for this run.
    rm -f /var/www/html/storage/app/.container-ready

    # Publish the multistage build from the image into the shared named volume (app_code).
    # .env is a separate bind mount and must not be overwritten.
    # storage/ is a separate named volume (app_storage) and keeps uploads/logs across deploys.
    if [ -d /opt/app-src ]; then
        mkdir -p /var/www/html

        rsync -a --delete \
            --exclude '.env' \
            --exclude '.env.*' \
            --exclude 'storage/' \
            /opt/app-src/ /var/www/html/

        mkdir -p \
            /var/www/html/storage/app/public \
            /var/www/html/storage/framework/cache/data \
            /var/www/html/storage/framework/sessions \
            /var/www/html/storage/framework/testing \
            /var/www/html/storage/framework/views \
            /var/www/html/storage/logs \
            /var/www/html/bootstrap/cache

        if [ -d /opt/app-src/storage/framework ] && [ ! -f /var/www/html/storage/framework/.gitignore ]; then
            rsync -a \
                --exclude 'cache/data/' \
                --exclude 'sessions/' \
                --exclude 'views/' \
                /opt/app-src/storage/framework/ /var/www/html/storage/framework/
        fi

        chown -R www-data:www-data \
            /var/www/html/storage \
            /var/www/html/bootstrap/cache \
            /var/www/html/public
    fi

    # Artisan setup after db healthcheck (compose depends_on). Not during image build.
    # Do not chown the bind-mounted application.env — keep the host file owner.
    if [ -f /var/www/html/artisan ] && [ -f /var/www/html/.env ]; then
        # One discover is enough. Laravel prints $manifest->manifest from *before* build(),
        # so the CLI list can look incomplete even when packages.php on disk is already correct.
        su -s /bin/sh www-data -c 'cd /var/www/html && php artisan package:discover --ansi --no-interaction'

        if ! grep -qE '^APP_KEY=base64:[^[:space:]]+' /var/www/html/.env; then
            # key:generate must write .env; run as root then restore host ownership/mode.
            env_owner="$(stat -c '%u:%g' /var/www/html/.env)"
            env_mode="$(stat -c '%a' /var/www/html/.env)"
            (cd /var/www/html && php artisan key:generate --no-interaction)
            chown "$env_owner" /var/www/html/.env
            chmod "$env_mode" /var/www/html/.env
        fi

        su -s /bin/sh www-data -c 'cd /var/www/html && php artisan storage:link --no-interaction'
        su -s /bin/sh www-data -c 'cd /var/www/html && php artisan migrate --force --no-interaction'
    fi

    # Signal compose healthcheck: volume sync + artisan setup finished (before php-fpm).
    mkdir -p /var/www/html/storage/app
    touch /var/www/html/storage/app/.container-ready
    chown www-data:www-data /var/www/html/storage/app/.container-ready
fi

exec "$@"
