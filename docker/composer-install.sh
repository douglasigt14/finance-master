#!/bin/sh
# Roda composer install como root (para criar vendor) e ajusta permissões para appuser.
# Use: docker compose exec app /docker/composer-install.sh
set -e
git config --global --add safe.directory /var/www/html
composer install "$@"
chown -R appuser:appgroup /var/www/html/vendor /var/www/html/.composer 2>/dev/null || true
