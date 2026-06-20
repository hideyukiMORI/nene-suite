FROM php:8.4-apache

# git/unzip are needed to fetch the NENE2 path dependency and let Composer
# install dist archives.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PDO MySQL and required extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html
RUN sed -i 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory "${APACHE_DOCUMENT_ROOT}">\n  AllowOverride All\n  Require all granted\n</Directory>' \
       >> /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# NENE2 is a Composer "path" repository (composer.json -> repositories: ../NENE2).
# The build context only contains this repo, so fetch NENE2 into ../NENE2 (relative
# to composer.json at /var/www/html) so `composer install` can resolve it.
# Override NENE2_GIT_REF with a tag for reproducible production builds.
ARG NENE2_GIT_URL=https://github.com/hideyukiMORI/NENE2.git
ARG NENE2_GIT_REF=main
RUN git clone --depth=1 --branch "${NENE2_GIT_REF}" "${NENE2_GIT_URL}" /var/www/NENE2

# Copy dependency definitions first (layer cache)
COPY composer.json composer.lock ./

COPY . .

# Install PHP dependencies (no dev — production image). Fail the build on error;
# a missing vendor/ must never ship silently.
RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 80
