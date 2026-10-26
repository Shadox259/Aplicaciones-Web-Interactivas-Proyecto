# syntax=docker/dockerfile:1

# =====================================================================================
# Library of Wonderland - imagen de distribucion (Apache + PHP 8.2.35 + la aplicacion)
#   docker build -t library-of-wonderland .
# La aplicacion se hornea en la imagen: no hace falta Composer, Node ni XAMPP para usarla.
# =====================================================================================

# ---------- 1) Dependencias de PHP ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts --no-interaction

# ---------- 2) Imagen final ----------
# 8.2.35 corrige CVE-2026-109618 y CVE-2026-109619 (RCE sin autenticacion, octubre 2026)
FROM php:8.2.35-apache

LABEL org.opencontainers.image.title="Library of Wonderland" \
      org.opencontainers.image.description="Biblioteca digital en Laravel: los autores publican libros en PDF y los lectores los leen gratis" \
      org.opencontainers.image.licenses="MIT"

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl libpq-dev \
    && docker-php-ext-install opcache pdo_mysql pdo_pgsql \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/wonderland.ini /usr/local/etc/php/conf.d/wonderland.ini
COPY docker/entrypoint.sh /usr/local/bin/wonderland-entrypoint

WORKDIR /var/www/html
COPY --from=vendor /app/ ./

RUN sed -i 's/\r$//' /usr/local/bin/wonderland-entrypoint \
    && chmod +x /usr/local/bin/wonderland-entrypoint \
    && mkdir -p storage/app/private/books storage/framework/cache/data storage/framework/sessions \
                storage/framework/views storage/logs bootstrap/cache public/uploads/covers \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache public/uploads \
    && chmod -R ug+rwX storage bootstrap/cache public/uploads

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=120s --retries=3 \
    CMD curl -fsS http://localhost/up > /dev/null || exit 1

ENTRYPOINT ["wonderland-entrypoint"]
CMD ["apache2-foreground"]
