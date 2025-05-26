@echo off
:: LabourLinks Website Launcher
:: Updated to ensure reliable startup

:: Verify PHP installation
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP not found in PATH
    echo Please install PHP from https://windows.php.net/download/
    echo Make sure to check "Add PHP to PATH" during installation
    pause
    exit /b
)

:: Kill any existing PHP server
taskkill /f /im php.exe >nul 2>&1

:: Start PHP server in current directory
echo Starting PHP server on port 3306...
start "PHP Server" /B php -S 0.0.0.0:3306 -t ./

:: Wait for server to initialize
timeout /t 3 >nul

:: Open browser to homepage
echo Opening website...
start "" "http://localhost:3306/index.html"

echo.
echo LabourLinks is running at:
echo http://localhost:3306
echo.
pause