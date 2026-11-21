#!/bin/sh
# Listen on the port the host gives us ($PORT on Railway/Render), prepare the database, start Apache.
set -e
# mod_php only works with the prefork MPM; make sure no other MPM is enabled
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
[ -e /etc/apache2/mods-enabled/mpm_prefork.load ] || a2enmod mpm_prefork >/dev/null

PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Uploaded files must live on a persistent volume mounted at /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/public/uploads 2>/dev/null || true
[ -f /var/www/html/public/uploads/.htaccess ] || cp /var/www/html/docker/uploads.htaccess /var/www/html/public/uploads/.htaccess 2>/dev/null || true

# Railway's MySQL also offers MYSQL_PRIVATE_URL / MYSQL_PUBLIC_URL; use them when MYSQL_URL is absent
[ -z "$MYSQL_URL" ] && [ -n "$MYSQL_PRIVATE_URL" ] && export MYSQL_URL="$MYSQL_PRIVATE_URL"
[ -z "$MYSQL_URL" ] && [ -n "$MYSQL_PUBLIC_URL" ] && export MYSQL_URL="$MYSQL_PUBLIC_URL"

# Which database variables reached the container (names only, never values)
for v in MYSQL_URL DATABASE_URL MYSQL_PRIVATE_URL MYSQL_PUBLIC_URL DB_HOST MYSQLHOST MYSQLUSER MYSQLDATABASE; do
  eval "val=\${$v-__unset__}"
  if [ "$val" = "__unset__" ]; then s="not set"; elif [ -z "$val" ]; then s="SET BUT EMPTY (check the reference)"; else s="ok"; fi
  echo "env $v: $s"
done

case "$MYSQL_URL$DATABASE_URL" in
  ""|mysql://*@*) ;;
  *) echo "!! MYSQL_URL / DATABASE_URL is not a mysql://user:pass@host/db address. The reference probably points at a"
     echo "!! service that does not exist: add a MySQL database to THIS project and set MYSQL_URL = \${{MySQL.MYSQL_URL}}"
     echo "!! (use the database service's exact name)." ;;
esac

# Create tables / apply migrations; retry while the database is still starting
if [ -z "${MYSQL_URL}${DATABASE_URL}${DB_HOST}${MYSQLHOST}" ]; then
  echo "!! No database settings: add MYSQL_URL = \${{MySQL.MYSQL_URL}} in the service's Variables (see DEPLOY.md)"
else
  # Write the settings to config/config.php so Apache's PHP reads them from a file
  # (environment variables are not always passed through to mod_php).
  php -r '$c = require "/var/www/html/config/config.env.php"; file_put_contents("/var/www/html/config/config.php", "<?php\n// Written at container start from environment variables. Do not edit.\nreturn " . var_export($c, true) . ";\n");'
  chown www-data:www-data /var/www/html/config/config.php && chmod 640 /var/www/html/config/config.php
  echo "Config written (database host: $(php -r '$c = require "/var/www/html/config/config.php"; echo $c["db"]["host"];'))"
fi

if [ -f /var/www/html/config/config.php ] && [ "${SKIP_DB_INSTALL:-0}" != "1" ]; then
  n=0
  until php /var/www/html/scripts/install_db.php; do
    n=$((n+1)); [ "$n" -ge 10 ] && { echo "Database not reachable; starting anyway"; break; }
    echo "Waiting for database ($n)…"; sleep 3
  done
fi

exec "$@"
