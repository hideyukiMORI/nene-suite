FROM php:8.4-apache

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

# Copy dependency definitions first (layer cache)
COPY composer.json composer.lock ./

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies (no dev — production image)
# NENE2 is a local path dependency; it must be present at ../NENE2 or replaced
# by a Composer package reference before production builds.
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || \
    echo "WARNING: composer install failed — NENE2 path dependency may not be present."

EXPOSE 80
