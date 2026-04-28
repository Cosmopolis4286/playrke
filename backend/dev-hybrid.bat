@echo off
echo [hybrid] Levantando servicios Docker (php, mysql, redis, reverb, queue, scheduler)...
docker compose -f docker-compose.hybrid.yml --env-file .env.dev up -d

echo.
echo [hybrid] Esperando que MySQL este listo...
:wait_mysql
docker exec playrke_mysql mysqladmin ping -h localhost -u root -p%DB_ROOT_PASSWORD% --silent >nul 2>&1
if errorlevel 1 (
    timeout /t 2 /nobreak >nul
    goto wait_mysql
)

echo [hybrid] MySQL listo.
echo.
echo [hybrid] Iniciando Vite HMR en el host (http://localhost:5173)...
echo [hybrid] Laravel corre en http://localhost:8000
echo [hybrid] Reverb corre en ws://localhost:8082
echo.
pnpm run dev
