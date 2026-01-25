# SVAIP - Quick Start Guide

## 🚀 Quick Start (Recommended)

### First Time Setup
```bash
# Run the complete setup (only needed once)
composer setup
```

This will:
- Install PHP dependencies
- Create `.env` file
- Generate application key
- Run database migrations
- Install NPM dependencies
- Build frontend assets

### Start Development Server

**Windows:**
```bash
start-dev.bat
```

**Linux/Mac:**
```bash
chmod +x start-dev.sh
./start-dev.sh
```

**Or use Composer directly:**
```bash
composer dev
```

This starts all services concurrently:
- 🌐 **Web Server**: http://localhost:8000
- ⚙️ **Queue Worker**: Background job processing
- 📋 **Log Viewer (Pail)**: Real-time log monitoring
- ⚡ **Vite Dev Server**: Hot module reloading for frontend

## 📝 Manual Commands

### Setup Steps (if not using composer setup)

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment configuration
cp .env.example .env
php artisan key:generate

# 3. Database setup
touch database/database.sqlite  # Linux/Mac
# OR
type nul > database\database.sqlite  # Windows

php artisan migrate

# 4. Build frontend assets
npm run build  # Production build
# OR
npm run dev  # Development with HMR
```

### Run Individual Services

```bash
# PHP Development Server only
php artisan serve

# Queue Worker only
php artisan queue:listen --tries=1

# Log Viewer only
php artisan pail --timeout=0

# Vite Dev Server only
npm run dev
```

### Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=Auth
php artisan test --filter=Scoring

# Run with coverage
php artisan test --coverage

# Run in parallel (faster)
php artisan test --parallel
```

### Database Management

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (drop all tables and re-migrate)
php artisan migrate:fresh

# Fresh migration with seeders
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status
```

### Cache Management

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan optimize
```

## 🌐 Accessing the Application

Once services are running:

- **Main Application**: http://localhost:8000
- **Register**: http://localhost:8000/register
- **Login**: http://localhost:8000/login
- **Flow Dashboard**: http://localhost:8000/ (after login)

## 🛑 Stopping Services

Press `Ctrl+C` in the terminal where services are running.

All services will stop automatically.

## 🔧 Troubleshooting

### Port Already in Use

If port 8000 is occupied:
```bash
php artisan serve --port=8001
```

### Permission Errors (Linux/Mac)

```bash
chmod -R 775 storage bootstrap/cache
```

### Database Connection Errors

Check `.env` file:
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

### NPM Errors

```bash
# Clear npm cache and reinstall
rm -rf node_modules package-lock.json
npm install
```

### Composer Errors

```bash
# Clear composer cache
composer clear-cache
composer install
```

## 📦 Production Deployment

```bash
# 1. Install dependencies (production only)
composer install --optimize-autoloader --no-dev
npm install --production

# 2. Build assets
npm run build

# 3. Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 4. Run migrations
php artisan migrate --force

# 5. Set permissions
chmod -R 755 storage bootstrap/cache
```

## 🔐 Environment Variables

Key variables in `.env`:

```env
APP_NAME=SVAIP
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite

# Optional: Email configuration for result sharing
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
```

## 📚 Additional Commands

### Interactive Shell (Tinker)

```bash
php artisan tinker
```

Useful for testing code interactively:
```php
>>> $user = App\Models\User::first();
>>> $flow = App\Models\Flow::factory()->create();
>>> $card = App\Models\Card::factory()->withScoring(0, 10)->create();
```

### List All Routes

```bash
php artisan route:list
```

### Generate IDE Helper (for better autocomplete)

```bash
composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
```

## 🎯 Development Workflow

1. Start services: `composer dev` (or `start-dev.bat`)
2. Make code changes
3. Frontend changes reload automatically (Vite HMR)
4. Backend changes require browser refresh
5. Run tests: `php artisan test`
6. Commit changes: `git add . && git commit -m "message"`

## 💡 Tips

- **Fast Test Feedback**: Run `php artisan test --filter=YourTest` during development
- **Watch Logs**: Pail shows real-time logs in the terminal
- **Hot Reload**: Vite automatically reloads frontend changes
- **Queue Processing**: Background jobs are processed automatically
- **Database Browser**: Use TablePlus, DBeaver, or DB Browser for SQLite

## 🆘 Need Help?

Check logs in:
- `storage/logs/laravel.log`
- Terminal output (when using `composer dev`)
- Browser console (for frontend issues)

Run diagnostics:
```bash
php artisan about
```
