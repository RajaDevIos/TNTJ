#!/bin/sh
# Listen on the port the host gives us ($PORT on Railway/Render), prepare the database, start Apache.
set -e
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Uploaded files must live on a persistent volume mounted at /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/public/uploads 2>/dev/null || true
[ -f /var/www/html/public/uploads/.htaccess ] || cp /var/www/html/docker/uploads.htaccess /var/www/html/public/uploads/.htaccess 2>/dev/null || true

# Create tables / apply migrations; retry while the database is still starting
if [ "${SKIP_DB_INSTALL:-0}" != "1" ]; then
  n=0
  until php /var/www/html/scripts/install_db.php; do
    n=$((n+1)); [ "$n" -ge 10 ] && { echo "Database not reachable; starting anyway"; break; }
    echo "Waiting for database ($n)…"; sleep 3
  done
fi

exec "$@"
