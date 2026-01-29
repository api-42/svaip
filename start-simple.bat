@echo off
REM SVAIP - Simple Server Start (No concurrency)
REM Use this if you have issues with the full dev environment

echo ========================================
echo   SVAIP - Starting Basic Server
echo ========================================
echo.

REM Check prerequisites
if not exist .env (
    echo [ERROR] .env file not found!
    echo Run 'php C:\php\composer.phar setup' first.
    pause
    exit /b 1
)

if not exist vendor (
    echo [ERROR] Dependencies not installed!
    echo Run 'php C:\php\composer.phar install' first.
    pause
    exit /b 1
)

echo [INFO] Checking database...
php artisan migrate --force
echo.

echo [INFO] Clearing caches...
php artisan config:clear
echo.

echo ========================================
echo   Server Starting
echo ========================================
echo.
echo Application: http://localhost:8001
echo.
echo Press Ctrl+C to stop
echo ========================================
echo.

REM Start simple server
php artisan serve --port=8001
