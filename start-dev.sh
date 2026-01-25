#!/bin/bash

echo "========================================"
echo "  SVAIP - Starting Development Server"
echo "========================================"
echo ""

# Color codes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env file exists
if [ ! -f .env ]; then
    echo -e "${BLUE}[INFO]${NC} Creating .env file from .env.example..."
    cp .env.example .env
    echo ""
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo -e "${BLUE}[INFO]${NC} Installing Composer dependencies..."
    composer install
    echo ""
fi

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo -e "${BLUE}[INFO]${NC} Installing NPM dependencies..."
    npm install
    echo ""
fi

# Generate application key if needed
if ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${BLUE}[INFO]${NC} Generating application key..."
    php artisan key:generate
    echo ""
fi

# Check database file exists (for SQLite)
if [ ! -f "database/database.sqlite" ]; then
    echo -e "${BLUE}[INFO]${NC} Creating SQLite database..."
    touch database/database.sqlite
    echo ""
fi

# Run migrations
echo -e "${BLUE}[INFO]${NC} Running database migrations..."
php artisan migrate --force
echo ""

# Clear caches
echo -e "${BLUE}[INFO]${NC} Clearing caches..."
php artisan config:clear
php artisan cache:clear
echo ""

echo "========================================"
echo "  Starting Development Services"
echo "========================================"
echo ""
echo -e "${GREEN}Services starting:${NC}"
echo "  - PHP Server (http://localhost:8001)"
echo "  - Queue Worker"
echo "  - Log Viewer (Pail)"
echo "  - Vite Dev Server (http://localhost:5173)"
echo ""
echo -e "${YELLOW}Press Ctrl+C to stop all services${NC}"
echo "========================================"
echo ""

# Open browser after a delay (in background)
(sleep 3 && (xdg-open http://localhost:8001 2>/dev/null || open http://localhost:8001 2>/dev/null)) &

# Start all development services
composer dev
