@echo off
echo ========================================
echo   SVAIP - Starting Development Server
echo ========================================
echo.

REM Check if .env file exists
if not exist .env (
    echo [INFO] Creating .env file from .env.example...
    copy .env.example .env
    echo.
)

REM Check if vendor directory exists
if not exist vendor (
    echo [INFO] Installing Composer dependencies...
    call php C:\php\composer.phar install
    echo.
)

REM Check if node_modules exists
if not exist node_modules (
    echo [INFO] Installing NPM dependencies...
    call npm install
    echo.
)

REM Generate application key if needed
findstr /C:"APP_KEY=" .env | findstr /C:"APP_KEY=base64:" >nul
if errorlevel 1 (
    echo [INFO] Generating application key...
    php artisan key:generate
    echo.
)

REM Check database file exists (for SQLite)
if not exist database\database.sqlite (
    echo [INFO] Creating SQLite database...
    type nul > database\database.sqlite
    echo.
)

REM Run migrations
echo [INFO] Running database migrations...
php artisan migrate --force
echo.

REM Clear caches
echo [INFO] Clearing caches...
php artisan config:clear
php artisan cache:clear
echo.

echo ========================================
echo   Starting Development Services
echo ========================================
echo.
echo Services starting:
echo   - PHP Server (http://localhost:8001)
echo   - Queue Worker
echo   - Vite Dev Server (http://localhost:5173)
echo.
echo NOTE: Log viewer (Pail) skipped - not available on Windows
echo.
echo Press Ctrl+C to stop all services
echo ========================================
echo.

REM Open browser after a delay (in background)
start /B powershell -Command "Start-Sleep -Seconds 3; Start-Process 'http://localhost:8001'"

REM Start all development services (Windows compatible - no pail)
php C:\php\composer.phar dev:windows
