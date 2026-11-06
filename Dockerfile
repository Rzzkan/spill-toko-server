# TemanSeller - image produksi (FrankenPHP/Caddy + PHP 8.3), melayani HTTP di port 3000 (di dalam container).
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --ignore-platform-reqs

FROM dunglas/frankenphp:1-php8.3
RUN install-php-extensions pdo_mysql mbstring gmp bcmath intl zip opcache pcntl gd \
    && printf 'upload_max_filesize=12M\npost_max_size=40M\nmax_file_uploads=10\n' > /usr/local/etc/php/conf.d/zz-uploads.ini
ENV SERVER_NAME=:3000 \
    CADDY_GLOBAL_OPTIONS="auto_https off" \
    APP_ENV=production APP_DEBUG=false
WORKDIR /app
COPY --from=vendor /app /app
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache
EXPOSE 3000
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s CMD curl -fsS -o /dev/null http://127.0.0.1:3000/robots.txt || exit 1
ENTRYPOINT ["entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
