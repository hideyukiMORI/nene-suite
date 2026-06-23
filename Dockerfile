# ---- Frontend build stage ----
# Build the React/Vite SPA to static assets. The app is served same-origin (it
# calls /api/v1 and /health on its own host), so no VITE_API_BASE_URL is needed
# at build time. node_modules / dist are excluded by .dockerignore, so this
# always builds fresh from a clean npm ci.
FROM node:22-slim AS frontend
WORKDIR /app/frontend
# .npmrc carries `legacy-peer-deps=true`, required for npm ci to resolve the
# eslint peer ranges — copy it before installing.
COPY frontend/package.json frontend/package-lock.json frontend/.npmrc ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

# ---- Runtime stage ----
FROM php:8.4-apache

# git/unzip are needed to fetch the NENE2 path dependency and let Composer
# install dist archives. libpq-dev provides the headers for the pdo_pgsql build.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PDO drivers: MySQL (default) and PostgreSQL (ADR 0016 — control DB engine
# is selected by the NENE_SUITE_CONTROL_DATABASE_URL scheme).
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql

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

# Place the built SPA into the Apache document root alongside index.php. The
# .htaccess routes /api + /health to the PHP front controller, serves real
# files (assets, index.html, openapi.php) directly, and falls back to
# index.html for client-side routes. Runs after `COPY . .` so the static
# index.html is added without clobbering index.php / .htaccess / openapi.php.
COPY --from=frontend /app/frontend/dist/. ${APACHE_DOCUMENT_ROOT}/

# Entrypoint applies pending phinx migrations on server start (idempotent), then
# hands off to the official PHP entrypoint. See ADR 0014. phinx ships in the
# production image because it is a `require` (not require-dev) dependency.
COPY ops/docker/entrypoint.sh /usr/local/bin/nene-suite-entrypoint
RUN chmod +x /usr/local/bin/nene-suite-entrypoint
ENTRYPOINT ["nene-suite-entrypoint"]
CMD ["apache2-foreground"]

EXPOSE 80
