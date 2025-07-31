#!/bin/bash

echo "🚀 [PROD] Copying .env.production → .env"
cp .env.production .env

echo "🚀 [PROD] Building frontend (Vite)"
vite build --mode production

echo "🚀 [PROD] Running Laravel on port 8001"
php artisan serve --host=0.0.0.0 --port=8001

