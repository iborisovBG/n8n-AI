FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Copy existing application directory permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy .env.example to .env if .env doesn't exist (will be overridden by volume mount in docker-compose)
# Note: Application key should be generated at runtime or via docker-compose command

# Expose port 8000 and start php-fpm server
EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
