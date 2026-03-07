#!/bin/sh
# Ajusta permissões de storage, bootstrap/cache e database para o appuser.
# Cria database.sqlite se não existir.
# Use: docker compose exec -u root app sh /var/www/html/docker/fix-permissions.sh
set -e
cd /var/www/html

# Garante que os diretórios existem
mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache storage/app bootstrap/cache

# Cria o SQLite se não existir
if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

# Ajusta dono para o usuário da aplicação
chown -R appuser:appgroup storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache
chmod 664 database/database.sqlite 2>/dev/null || true

echo "Permissões ajustadas. storage/, bootstrap/cache e database/ estão writable para appuser."
