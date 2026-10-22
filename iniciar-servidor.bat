@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Library of Wonderland - Servidor de desarrollo
color 0F

rem Sirve el sitio con "php artisan serve" (no necesita Apache, pero si MySQL encendido).

set "PHP="
for %%D in (C D E F) do (
    if not defined PHP if exist "%%D:\xampp\php\php.exe" set "PHP=%%D:\xampp\php\php.exe"
)
if not defined PHP (
    for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP set "PHP=%%P"
)
if not defined PHP (
    echo [X] No se encontro PHP. Instala XAMPP con PHP 8.2 o superior.
    pause
    exit /b 1
)
if not exist "vendor\autoload.php" (
    echo [X] Faltan las dependencias. Ejecuta primero instalar-xampp.bat
    pause
    exit /b 1
)
if not exist ".env" (
    echo [X] Falta el archivo .env. Ejecuta primero instalar-xampp.bat
    pause
    exit /b 1
)

powershell -NoProfile -Command "try { $c = New-Object Net.Sockets.TcpClient; $c.Connect('127.0.0.1',3306); $c.Close(); exit 0 } catch { exit 1 }"
if errorlevel 1 (
    echo [!] MySQL no responde en el puerto 3306. Enciendelo desde el Panel de Control de XAMPP.
    pause
    exit /b 1
)

echo.
echo  Sitio:  http://127.0.0.1:8000     ^(Ctrl+C para detener^)
echo.
start "" "http://127.0.0.1:8000"
"%PHP%" artisan serve --host=127.0.0.1 --port=8000
endlocal
