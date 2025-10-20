@echo off
REM ============================================================
REM Daily Stock Alert Check - Batch Script
REM ============================================================
REM 
REM Script này chạy kiểm tra tồn kho hàng ngày và tạo thông báo
REM Được thiết kế để chạy tự động lúc 7h sáng mỗi ngày
REM 
REM HƯỚNG DẪN CÀI ĐẶT:
REM 1. Mở Task Scheduler (taskschd.msc)
REM 2. Create Basic Task
REM 3. Name: "Daily Stock Alert - 7AM"
REM 4. Trigger: Daily at 07:00:00
REM 5. Action: Start this batch file
REM 6. Program: C:\Users\Dell\OneDrive\Documents\Course\CT550\daily_stock_check.bat
REM 
REM CÁCH CHẠY THỬ:
REM Double-click file này hoặc chạy từ CMD
REM 
REM ============================================================

echo.
echo ========================================
echo   DAILY STOCK ALERT CHECK
echo ========================================
echo   Starting at: %DATE% %TIME%
echo ========================================
echo.

REM Chuyển đến thư mục dự án
cd /d "%~dp0"

REM Chạy PHP script
php daily_stock_check.php

REM Kiểm tra kết quả
if %ERRORLEVEL% EQU 0 (
    echo.
    echo [SUCCESS] Daily stock check completed successfully!
    echo Check logs\daily_stock_check.log for details
) else (
    echo.
    echo [ERROR] Daily stock check failed! Error code: %ERRORLEVEL%
    echo Check logs\daily_stock_check.log for error details
)

echo.
echo ========================================
echo   Finished at: %DATE% %TIME%
echo ========================================
echo.

REM Tự động đóng sau 5 giây (bỏ dòng này nếu muốn xem kết quả)
REM timeout /t 5 /nobreak >nul
