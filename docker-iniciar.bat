@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Library of Wonderland - Docker

where docker >nul 2>nul
if errorlevel 1 (
    echo [X] Docker no esta instalado. Descargalo de https://www.docker.com/products/docker-desktop/
    pause
    exit /b 1
)
docker info >nul 2>nul
if errorlevel 1 (
    echo [X] Docker Desktop no esta corriendo. Abrelo, espera a que diga "Engine running" y vuelve a ejecutar este archivo.
    pause
    exit /b 1
)

echo.
echo  Descargando la imagen publicada de la aplicacion ^(y MariaDB^)...
docker compose pull
if errorlevel 1 (
    echo.
    echo  [!] No se pudo descargar la imagen publicada ^(aun no existe en GitHub o no hay acceso a Internet^).
    echo      Se construira localmente a partir de este codigo. La primera vez tarda varios minutos.
    echo.
    docker compose -f docker-compose.yml -f docker-compose.build.yml up -d --build
) else (
    docker compose up -d
)
if errorlevel 1 (
    echo [X] Fallo "docker compose". Revisa el mensaje de arriba.
    echo     Si dice "context deadline exceeded" o "unauthorized": mira la seccion "Problemas frecuentes" del README.
    pause
    exit /b 1
)

echo.
echo  Esperando a que la aplicacion este lista ^(crea la base de datos y carga los datos de demostracion^)...
powershell -NoProfile -Command "$t=0; while($t -lt 600){ try { $r = Invoke-WebRequest -UseBasicParsing -Uri 'http://localhost:8080/up' -TimeoutSec 3; if($r.StatusCode -eq 200){ exit 0 } } catch {}; Start-Sleep -Seconds 3; $t+=3; Write-Host -NoNewline '.' }; exit 1"
if errorlevel 1 (
    echo.
    echo [!] Aun no responde. Mira el progreso con:  docker compose logs -f app
    pause
    exit /b 1
)

echo.
echo  ==========================================================
echo    LISTO:  http://localhost:8080
echo  ==========================================================
echo.
echo  Cuentas de demostracion:
echo    admin@wonderland.test / admin123   ^|   autor@wonderland.test / autor123   ^|   cliente@wonderland.test / cliente123
echo.
echo  Para detener todo: docker-detener.bat
start "" "http://localhost:8080"
pause
endlocal