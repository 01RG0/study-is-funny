@echo off
REM ============================================================
REM Study is Funny - Local Website Startup Script
REM Simulates Hostinger environment for local development
REM ============================================================

echo.
echo ============================================================
echo   STUDY IS FUNNY - LOCAL WEBSITE STARTUP
echo   Simulating Hostinger Environment
echo ============================================================
echo.

REM Check PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed or not in PATH
    echo Please install PHP 7.4+ and add it to your PATH
    pause
    exit /b 1
)

echo [OK] PHP is installed
php --version | findstr "PHP"
echo.

REM Check MongoDB extension
php -m | findstr "mongodb" >nul 2>&1
if %errorlevel% neq 0 (
    echo [WARNING] MongoDB extension not loaded
    echo Payment system will use fallback mode
) else (
    echo [OK] MongoDB extension loaded
)
echo.

REM Check required extensions
echo Checking PHP extensions...
for %%E in (pdo session json curl mbstring openssl) do (
    php -m | findstr "%%E" >nul 2>&1
    if %errorlevel% neq 0 (
        echo [WARNING] Extension missing: %%E
    ) else (
        echo [OK] Extension: %%E
    )
)
echo.

REM Check .env file
if not exist .env (
    echo [WARNING] .env file not found
    echo Creating default .env file...
    echo MONGO_URI=mongodb://localhost:27017 > .env
    echo DB_NAME=study_is_funny >> .env
    echo APP_NAME=Study is Funny >> .env
    echo BASE_PATH=%CD% >> .env
    echo UPLOADS_DIR=%CD%\uploads >> .env
    echo SESSION_TIMEOUT=3600 >> .env
    echo [OK] Created .env file
) else (
    echo [OK] .env file exists
)
echo.

REM Create necessary directories
echo Creating directories...
if not exist uploads mkdir uploads
if not exist uploads\payments mkdir uploads\payments
if not exist uploads\videos mkdir uploads\videos
if not exist uploads\homework mkdir uploads\homework
if not exist logs mkdir logs
echo [OK] Directories created/verified
echo.

REM Check MongoDB connection (optional)
echo Checking MongoDB connection...
php -r "try { $m = new MongoDB\Client('mongodb://localhost:27017'); echo '[OK] MongoDB is running\n'; } catch (Exception $e) { echo '[WARNING] MongoDB not running - using fallback mode\n'; }" 2>nul
echo.

REM Start PHP development server
echo ============================================================
echo   STARTING PHP DEVELOPMENT SERVER
echo ============================================================
echo.
echo Server will run on: http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.
echo ============================================================
echo.

REM Start server
php -S localhost:8000 router.php

REM If server exits
echo.
echo ============================================================
echo   SERVER STOPPED
echo ============================================================
echo.
pause
