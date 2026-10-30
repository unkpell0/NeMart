FROM php:8.2-apache

# SQLite untuk database demo (tanpa layanan eksternal)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && a2enmod headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Folder internal tidak boleh diakses langsung dari browser.
RUN printf '\n<Directory /var/www/html/data>\n    Require all denied\n</Directory>\n<Directory /var/www/html/includes>\n    Require all denied\n</Directory>\n<Directory /var/www/html/db>\n    Require all denied\n</Directory>\n' >> /etc/apache2/apache2.conf

RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && mkdir -p /var/www/html/data/sessions \
    && chown -R www-data:www-data /var/www/html/data

EXPOSE 10000

ENTRYPOINT ["sh", "/usr/local/bin/docker-entrypoint.sh"]
