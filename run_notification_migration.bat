@echo off
echo Running notification system migration...
echo.

mysql -u root mini_market < database\migrations\add_notifications.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo Migration completed successfully!
    echo.
    echo Checking notifications table...
    echo SELECT COUNT(*) as total_notifications FROM notifications; | mysql -u root mini_market
) else (
    echo.
    echo Migration failed! Please check your database connection.
)

pause
