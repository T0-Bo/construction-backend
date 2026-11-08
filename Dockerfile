FROM php:8.5.12-fpm-trixie

ARG HOST_GID=1000

COPY --from=composer:2.9.3 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libicu-dev \
    libsqlite3-dev \
    passwd \
    && docker-php-ext-install \
        bcmath \
        intl \
        pdo_mysql \
	pdo_sqlite \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && if getent group "${HOST_GID}" >/dev/null; then \
        HOST_GROUP="$(getent group "${HOST_GID}" | cut -d: -f1)"; \
    else \
        groupadd --gid "${HOST_GID}" hostshared; \
        HOST_GROUP=hostshared; \
    fi \
    && usermod -aG "${HOST_GROUP}" www-data \
    && rm -rf /var/lib/apt/lists/*
