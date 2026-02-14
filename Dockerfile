FROM php:8.4-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    && docker-php-ext-install \
    pdo_mysql \
    intl \
    zip \
    opcache \
    gd

# Installer Composer 2.x (version spécifique au lieu de latest)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier composer.json et composer.lock D'ABORD
COPY composer.json composer.lock symfony.lock ./

# Installer les dépendances (respecte composer.lock)
RUN composer install --no-interaction --no-scripts --prefer-dist

# Copier le reste des fichiers
COPY . .

# Finaliser l'installation avec optimisation
RUN composer install --no-interaction --optimize-autoloader

EXPOSE 9000

CMD ["php-fpm"]