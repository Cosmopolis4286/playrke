@echo off
echo ========================================
echo Iniciando servidor Laravel
echo ========================================

echo.
echo [1/5] Limpiando cache de configuracion...
php artisan config:clear

echo.
echo [2/5] Limpiando cache de rutas...
php artisan route:clear

echo.
echo [3/5] Limpiando cache de vistas...
php artisan view:clear

echo.
echo [4/5] Limpiando cache de aplicacion...
php artisan cache:clear

echo.
echo [5/5] Iniciando servidor en http://127.0.0.1:8000
echo.
echo ========================================
echo Servidor corriendo!
echo Presiona Ctrl+C para detener
echo ========================================
echo.

php artisan serve --host=127.0.0.1 --port=8000
