@echo off
echo.
echo ========================================
echo   TEST STOCK ALERTS SYSTEM
echo ========================================
echo.

cd /d "%~dp0"

echo [1/3] Testing PHP script...
php daily_stock_check.php
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PHP script failed!
    pause
    exit /b 1
)

echo.
echo [2/3] Checking log file...
if exist "logs\daily_stock_check.log" (
    echo [OK] Log file created successfully
    echo.
    echo === Last 10 lines of log ===
    powershell -Command "Get-Content logs\daily_stock_check.log -Tail 10"
) else (
    echo [WARNING] Log file not found
)

echo.
echo [3/3] Opening website to check notifications...
echo Please login and check the bell icon (top right)
timeout /t 2 >nul
start http://localhost/admin/stocks

echo.
echo ========================================
echo   TEST COMPLETED
echo ========================================
echo.
echo Next steps:
echo 1. Check bell icon for notifications
echo 2. If OK, setup Task Scheduler
echo 3. See QUICK_SETUP_ALERTS.md
echo.
pause
