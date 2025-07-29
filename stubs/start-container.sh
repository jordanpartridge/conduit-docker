#!/bin/bash

# Wait for database
echo "Waiting for database..."
while ! nc -z database 5432; do
  sleep 1
done

echo "Database is ready!"

# Run migrations
php artisan migrate --force

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf