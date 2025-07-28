#!/bin/bash

echo "🚀 [PROD] Copying .env.production → .env"
cp .env.production .env

echo "🚀 [PROD] Building frontend (Vite)"
npm run build

echo "🚀 [PROD] Running Laravel on port 8001"
php artisan serve --port=8001
