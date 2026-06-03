@echo off
set "ROOT=%~dp0"
if "%ROOT:~-1%"=="\" set "ROOT=%ROOT:~0,-1%"
echo Project folder: %ROOT%
if not exist "%ROOT%\router.php" (
    echo ERROR: router.php not found in %ROOT%
    pause
    exit /b 1
)
php -S localhost:8000 -t "%ROOT%" "%ROOT%\router.php"
