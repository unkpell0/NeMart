#!/bin/sh
# Render memberi variabel PORT; Apache perlu diberi tahu port tersebut.
set -e

PORT="${PORT:-80}"

if [ "$PORT" != "80" ]; then
    sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

mkdir -p /var/www/html/data/sessions
chown -R www-data:www-data /var/www/html/data 2>/dev/null || true

# Skema + seed dijalankan otomatis oleh bootstrap pada request pertama.
exec apache2-foreground
