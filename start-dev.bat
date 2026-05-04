@echo off
setlocal

set "ROOT=%~dp0"
set "PYTHON_DIR=%ROOT%python_service"
set "PHP_HOST=127.0.0.1"
set "PHP_PORT=8000"

echo EcoMarine dev launcher
echo.
echo Python API: http://localhost:5000
echo Symfony app: http://%PHP_HOST%:%PHP_PORT%
echo.

if not exist "%PYTHON_DIR%\start.bat" (
    echo Missing file: %PYTHON_DIR%\start.bat
    exit /b 1
)

where php >nul 2>nul
if errorlevel 1 (
    echo PHP is not available in PATH.
    exit /b 1
)

start "EcoMarine Python API" cmd /k "cd /d ""%PYTHON_DIR%"" && call start.bat"
start "EcoMarine Symfony" cmd /k "cd /d ""%ROOT%"" && php -S %PHP_HOST%:%PHP_PORT% -t public"

echo.
echo Both services were launched in separate windows.
echo Wait for the Python installer to finish, then open http://%PHP_HOST%:%PHP_PORT%
