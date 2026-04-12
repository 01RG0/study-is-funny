@echo off
setlocal enabledelayedexpansion

:: ──────────────────────────────────────────────────────────
::  Study is Funny - Test Launcher
:: ──────────────────────────────────────────────────────────

set "PORT=8000"
set "BASEDIR=%~dp0"

:MENU
cls
echo.
echo  ╔═════════════════════════════════════════════════════╗
echo  ║     STUDY IS FUNNY - TEST LAUNCHER                   ║
echo  ╚═════════════════════════════════════════════════════╝
echo.
echo   [1] Run ALL tests (offline - no server needed)
echo   [2] Start dev server + run ALL tests (includes API)
echo   [3] Start dev server only
echo   [4] Run tests with verbose output
echo   [5] Run tests (skip DB connection tests)
echo   [6] Run tests (skip API endpoint tests)
echo   [7] Open test dashboard in browser
echo   [0] Exit
echo.
set /p CHOICE="  Choose option: "

if "%CHOICE%"=="1" goto OFFLINE_TESTS
if "%CHOICE%"=="2" goto FULL_TESTS
if "%CHOICE%"=="3" goto SERVER_ONLY
if "%CHOICE%"=="4" goto VERBOSE_TESTS
if "%CHOICE%"=="5" goto SKIP_DB_TESTS
if "%CHOICE%"=="6" goto SKIP_API_TESTS
if "%CHOICE%"=="7" goto OPEN_DASHBOARD
if "%CHOICE%"=="0" goto END
goto MENU

:: ─── Offline tests (no server) ────────────────────────────
:OFFLINE_TESTS
echo.
echo  Running offline tests (skip API endpoints)...
echo.
php "%BASEDIR%test-runner.php" --skip-api
echo.
pause
goto MENU

:: ─── Full tests: start server, wait, test, kill server ───
:FULL_TESTS
echo.
echo  Starting dev server on port %PORT%...
start /B php -S localhost:%PORT% "%BASEDIR%router.php" >nul 2>&1
timeout /t 2 /nobreak >nul

echo  Running full test suite...
echo.
php "%BASEDIR%test-runner.php"
set TESTRESULT=%ERRORLEVEL%

echo.
echo  Stopping dev server...
taskkill /F /FI "WINDOWTITLE eq php*" >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%PORT% " ^| findstr LISTENING') do (
    taskkill /F /PID %%a >nul 2>&1
)

echo.
if %TESTRESULT%==0 (
    echo  All tests passed!
) else (
    echo  Some tests failed - see output above.
)
echo.
pause
goto MENU

:: ─── Server only ──────────────────────────────────────────
:SERVER_ONLY
echo.
echo  Starting dev server on http://localhost:%PORT%
echo  Press Ctrl+C to stop.
echo.
php -S localhost:%PORT% "%BASEDIR%router.php"
pause
goto MENU

:: ─── Verbose tests ────────────────────────────────────────
:VERBOSE_TESTS
echo.
echo  Running tests with verbose output...
echo.
php "%BASEDIR%test-runner.php" --verbose --skip-api
echo.
pause
goto MENU

:: ─── Skip DB tests ────────────────────────────────────────
:SKIP_DB_TESTS
echo.
echo  Running tests (skipping database connection)...
echo.
php "%BASEDIR%test-runner.php" --skip-db --skip-api
echo.
pause
goto MENU

:: ─── Skip API tests ────────────────────────────────────────
:SKIP_API_TESTS
echo.
echo  Running tests (skipping API endpoints)...
echo.
php "%BASEDIR%test-runner.php" --skip-api
echo.
pause
goto MENU

:: ─── Open dashboard ───────────────────────────────────────
:OPEN_DASHBOARD
echo.
echo  Starting server and opening test dashboard...
start /B php -S localhost:%PORT% "%BASEDIR%router.php" >nul 2>&1
timeout /t 2 /nobreak >nul
start http://localhost:%PORT%/api/test_all_functions.php
echo  Dashboard opened at http://localhost:%PORT%/api/test_all_functions.php
echo  Close this window to stop the server.
echo.
pause
goto MENU

:END
endlocal
