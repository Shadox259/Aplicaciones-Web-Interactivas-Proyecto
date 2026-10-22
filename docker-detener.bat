@echo off
cd /d "%~dp0"
echo Deteniendo contenedores (los datos de la base de datos se conservan)...
docker compose down
pause
