@echo off
:: LabourLinks Server Launcher for Windows

:: Stop any existing Python servers
taskkill /f /im python.exe >nul 2>&1

:: Set port and start Python server
set PORT=3306
echo Starting Python server on port %PORT%...
python server.py

echo LabourLinks is running at:
echo http://localhost:%PORT%
pause
