@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Library of Wonderland - Instalador para XAMPP
color 0F

echo.
echo  ==========================================================
echo    LIBRARY OF WONDERLAND  -  Instalador para XAMPP
echo  ==========================================================
echo.

rem ---------- 1) Buscar PHP (primero el de XAMPP) ----------
set "PHP="
for %%D in (C D E F) do (
    if not defined PHP if exist "%%D:\xampp\php\php.exe" set "PHP=%%D:\xampp\php\php.exe"
)
if not defined PHP (
    for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP set "PHP=%%P"
)
if not defined PHP (
    echo [X] No se encontro PHP. Instala XAMPP con PHP 8.2 o superior:
    echo     https://www.apachefriends.org/download.html
    echo.
    pause
    exit /b 1
)
echo [OK] PHP encontrado: %PHP%

"%PHP%" -r "exit(version_compare(PHP_VERSION,'8.2.0','>=') ? 0 : 1);"
if errorlevel 1 (
    echo [X] Laravel 12 necesita PHP 8.2 o superior y tu version es:
    "%PHP%" -v | findstr /b "PHP"
    echo     Descarga la version de XAMPP con PHP 8.2.x o 8.3.x.
    echo.
    pause
    exit /b 1
)

rem ---------- 2) Dependencias (carpeta vendor) ----------
if exist "vendor\autoload.php" (
    echo [OK] Las dependencias ya estan instaladas ^(carpeta vendor^).
) else (
    set "COMPOSER="
    where composer >nul 2>nul && set "COMPOSER=composer"
    if not defined COMPOSER if exist "composer.phar" set COMPOSER="%PHP%" composer.phar
    if not defined COMPOSER (
        echo [..] Composer no esta instalado. Descargando composer.phar ...
        powershell -NoProfile -ExecutionPolicy Bypass -Command "Invoke-WebRequest https://getcomposer.org/composer-stable.phar -OutFile composer.phar"
        if exist "composer.phar" set COMPOSER="%PHP%" composer.phar
    )
    if not defined COMPOSER (
        echo [X] No se pudo obtener Composer. Instalalo desde https://getcomposer.org/download/
        echo     y vuelve a ejecutar este archivo.
        pause
        exit /b 1
    )
    echo [..] Instalando dependencias con Composer ^(puede tardar unos minutos^)...
    call %COMPOSER% install --no-interaction --prefer-dist
    if errorlevel 1 (
        echo [X] Fallo "composer install". Revisa tu conexion a Internet.
        pause
        exit /b 1
    )
)

rem ---------- 3) Archivo .env y clave de la aplicacion ----------
if not exist ".env" (
    copy /y ".env.example" ".env" >nul
    echo [OK] Se creo el archivo .env ^(valores de XAMPP: MySQL root sin contrasena^).
)
findstr /b /c:"APP_KEY=base64:" ".env" >nul 2>nul
if errorlevel 1 "%PHP%" artisan key:generate --force

rem ---------- 4) MySQL encendido? ----------
:check_mysql
powershell -NoProfile -Command "try { $c = New-Object Net.Sockets.TcpClient; $c.Connect('127.0.0.1',3306); $c.Close(); exit 0 } catch { exit 1 }"
if errorlevel 1 (
    echo.
    echo [!] MySQL no responde en el puerto 3306.
    echo     Abre el Panel de Control de XAMPP ^(xampp-control.exe^) y pulsa "Start"
    echo     en MySQL y tambien en Apache.
    echo.
    choice /c RS /n /m "Pulsa R para reintentar o S para salir: "
    if errorlevel 2 exit /b 1
    goto check_mysql
)
echo [OK] MySQL esta encendido.

rem ---------- 5) Crear base de datos, tablas y datos de demostracion ----------
echo.
"%PHP%" artisan wonderland:install
if errorlevel 1 (
    echo.
    echo [X] La instalacion no termino. Lee el mensaje de arriba.
    pause
    exit /b 1
)

rem ---------- 6) Listo ----------
for %%I in ("%~dp0.") do set "FOLDER=%%~nxI"
echo.
echo  ==========================================================
echo    LISTO. Abre:  http://localhost/%FOLDER%/public
echo  ==========================================================
echo.
echo  Cuentas de demostracion:
echo    Administrador ^> admin@wonderland.test    / admin123
echo    Autor         ^> autor@wonderland.test    / autor123
echo    Cliente       ^> cliente@wonderland.test  / cliente123
echo.
echo  Si esta carpeta NO esta dentro de htdocs ^(C:\xampp\htdocs^), copiala ahi
echo  o usa iniciar-servidor.bat para abrir el sitio sin Apache.
echo.
echo %~dp0 | findstr /i "htdocs" >nul && start "" "http://localhost/%FOLDER%/public"
pause
endlocal
