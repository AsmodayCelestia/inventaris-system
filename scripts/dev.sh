#!/bin/bash

echo "🧪 [DEV] Copying .env.local → .env"
cp .env.local .env

echo "🧪 [DEV] Running Laravel on port 8000"
php artisan serve --port=8000