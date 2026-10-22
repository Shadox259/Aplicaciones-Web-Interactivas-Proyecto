@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Library of Wonderland - Vista rapida (sin instalar nada)
color 0F

echo.
echo  ==========================================================
echo    LIBRARY OF WONDERLAND  -  Vista rapida
echo    PHP portatil + SQLite: NO necesita XAMPP, MySQL, Docker
echo    ni Composer. Solo para ver y probar la pagina.
echo  ==========================================================
echo.

rem ---------- PHP: el portatil incluido (_php), luego XAMPP, luego el del PATH ----------
set "PHP="
if exist "%~dp0_php\php.exe" set "PHP=%~dp0_php\php.exe"
if not defined PHP (
    for %%D in (C D E F) do (
        if not defined PHP if exist "%%D:\xampp\php\php.exe" set "PHP=%%D:\xampp\php\php.exe"
    )
)
if not defined PHP (
    for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP set "PHP=%%P"
)
if not defined PHP (
    echo [X] No se encontro PHP 8.2 o superior.
    echo     Copia aqui la carpeta "_php" ^(PHP portatil^) o instala XAMPP con PHP 8.2.
    echo.
    pause
    exit /b 1
)
echo [OK] PHP: %PHP%

if not exist "vendor\autoload.php" (
    echo [X] Faltan las dependencias ^(carpeta vendor^). Ejecuta instalar-xampp.bat o "composer install".
    pause
    exit /b 1
)

rem ---------- Archivo .env y clave de la aplicacion (se crean solos la primera vez) ----------
if not exist ".env" copy /y ".env.example" ".env" >nul
findstr /b /c:"APP_KEY=base64:" ".env" >nul 2>nul
if errorlevel 1 "%PHP%" artisan key:generate --force >nul

rem ---------- Base de datos SQLite de demostracion (se crea sola la primera vez) ----------
set "DB_CONNECTION=sqlite"
set "DB_DATABASE=%~dp0database\demo.sqlite"
set "APP_ENV=local"
set "APP_DEBUG=true"
set "WONDERLAND_SHOW_DEMO=true"

if not exist "database\demo.sqlite" (
    echo [..] Creando la base de datos de demostracion ^(unos segundos^)...
    "%PHP%" artisan wonderland:install --no-interaction
    if errorlevel 1 (
        echo [X] No se pudo preparar la base de datos. Revisa el mensaje de arriba.
        pause
        exit /b 1
    )
)

rem ---------- Puerto libre (8000 o el siguiente) ----------
set "PORT=8000"
for /f %%P in ('powershell -NoProfile -Command "$p=8000; while (Get-NetTCPConnection -LocalPort $p -State Listen -ErrorAction SilentlyContinue) { $p++ }; $p"') do set "PORT=%%P"
set "APP_URL=http://127.0.0.1:%PORT%"

echo.
echo  ==========================================================
echo    Abriendo  %APP_URL%      ^(Ctrl+C para detener^)
echo.
echo    Administrador: admin@wonderland.test   / admin123
echo    Autor:         autor@wonderland.test   / autor123
echo    Cliente:       cliente@wonderland.test / cliente123
echo  ==========================================================
echo.
start "" "%APP_URL%"
"%PHP%" -S 127.0.0.1:%PORT% -t public
endlocal
