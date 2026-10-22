@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Library of Wonderland - Reiniciar datos

echo.
echo  ATENCION: esto BORRA todas las tablas de la base de datos y vuelve a cargar
echo  los datos de demostracion (usuarios, libros, libros guardados y resenas).
echo.
choice /c SN /n /m "Continuar? (S/N): "
if errorlevel 2 exit /b 0

set "PHP="
for %%D in (C D E F) do (
    if not defined PHP if exist "%%D:\xampp\php\php.exe" set "PHP=%%D:\xampp\php\php.exe"
)
if not defined PHP (
    for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP set "PHP=%%P"
)
if not defined PHP (
    echo [X] No se encontro PHP.
    pause
    exit /b 1
)

"%PHP%" artisan wonderland:install --fresh
pause
endlocal
