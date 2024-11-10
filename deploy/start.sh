 #!/bin/sh

# Ensure database directory exists and has correct permissions
mkdir -p /var/www/html/database
touch /var/www/html/database/database.sqlite
chmod -R 775 /var/www/html/database

# Run migrations
php artisan migrate --force

# Run seeders
php artisan db:seed --force

# Start queue worker in the background
php artisan queue:work --queue=orders --tries=3 --timeout=90 &

# Start PHP-FPM
php-fpm
