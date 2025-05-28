#!/bin/bash

echo "🚀 Starting deployment..."

# Run database migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Seed database if needed (only on first deploy)
# php artisan db:seed --force

# Clear and cache configurations
echo "🔧 Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Create storage symlink
echo "🔗 Creating storage symlink..."
php artisan storage:link

# Generate Ziggy routes
echo "🛣️ Generating Ziggy routes..."
php artisan ziggy:generate

echo "✅ Deployment complete!"
