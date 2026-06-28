#!/bin/bash
set -e

# On Azure App Service, uploaded images must live on the persistent /home mount,
# otherwise they are stored inside the (ephemeral) container filesystem and are
# wiped on every redeploy. /home is durable when
# WEBSITES_ENABLE_APP_SERVICE_STORAGE=true.
#
# We point the web-served uploads directory at a persistent location by
# symlinking /var/www/html/uploads -> /home/site/uploads.
#
# Guarded on WEBSITE_SITE_NAME (only set on Azure App Service) so local
# docker-compose, which bind-mounts ./uploads, is left completely untouched.
if [ -n "${WEBSITE_SITE_NAME:-}" ]; then
    PERSIST_DIR="/home/site/uploads"
    WEB_UPLOADS="/var/www/html/uploads"

    mkdir -p "$PERSIST_DIR"

    if [ -d "$WEB_UPLOADS" ] && [ ! -L "$WEB_UPLOADS" ]; then
        # Migrate anything baked into the image, then replace the dir with a symlink.
        cp -an "$WEB_UPLOADS/." "$PERSIST_DIR/" 2>/dev/null || true
        rm -rf "$WEB_UPLOADS"
    fi
    ln -sfn "$PERSIST_DIR" "$WEB_UPLOADS"

    chown -R www-data:www-data "$PERSIST_DIR" 2>/dev/null || true
    chmod -R 775 "$PERSIST_DIR" 2>/dev/null || true
fi

exec apache2-foreground
