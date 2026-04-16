FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    postgresql-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql bcmath gd

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy files
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev --no-scripts --no-interaction
RUN npm install && npm run build

# Set permissions
RUN mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

# Nginx config
RUN printf 'server {\n\
    listen 10000;\n\
    root /var/www/html/public;\n\
    index index.php;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location ~ \.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n\
        fastcgi_param HTTPS on;\n\
        fastcgi_param HTTP_X_FORWARDED_PROTO https;\n\
        include fastcgi_params;\n\
    }\n\
}\n' > /etc/nginx/http.d/default.conf

# Start script
RUN printf '#!/bin/sh\n\
echo "Starting php-fpm..."\n\
php-fpm -D\n\
sleep 1\n\
echo "Clearing caches..."\n\
php artisan config:clear || true\n\
php artisan cache:clear || true\n\
echo "Running migrations..."\n\
php artisan migrate --force || true\n\
echo "Starting scheduler..."\n\
(while true; do php artisan schedule:run --no-interaction >> /var/log/scheduler.log 2>&1; sleep 60; done) &\n\
echo "Starting nginx..."\n\
exec nginx -g "daemon off;"\n' > /start.sh && chmod +x /start.sh

EXPOSE 10000

CMD ["/start.sh"]
