<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\AppParameterRepository;
use Illuminate\Support\Facades\Log;

class AppParametersConfigServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios
     */
    public function register(): void
    {
        // No requiere registro adicional
    }

    /**
     * Inicializar servicios después del registro
     */
    public function boot(): void
    {
        try {
            // Inyectamos el repositorio
            $parameterRepo = app(AppParameterRepository::class);

            // Obtenemos todos los parámetros activos
            $allParameters = $parameterRepo->getActiveSetting();

            foreach ($allParameters as $parameter) {
                // Convertimos el parámetro a minúsculas y snake_case
                $param = strtolower($parameter->param);

                // Solo seteamos si no existe en config
                if (!config()->has("app.{$param}")) {
                    config()->set("app.{$param}", $parameter->getFormattedValue());
                }
            }
        } catch (\Throwable $e) {
            // Opcional: log del error para debugging
            Log::error('Error al cargar AppParametersConfig: ' . $e->getMessage());
        }

        // Valores de fallback desde .env
        $envFallbacks = [
            'name' => env('APP_NAME', 'Laravel'),
            'env' => env('APP_ENV', 'production'),
            'debug' => (bool) env('APP_DEBUG', false),
            'url' => env('APP_URL', 'http://localhost'),
            'timezone' => env('APP_TIMEZONE', 'UTC'),
            'locale' => env('APP_LOCALE', 'en'),
            'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
            'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
            'key' => env('APP_KEY'),
            'cipher' => 'AES-256-CBC',
            'version' => env('APP_VERSION', '1.0'),
        ];

        foreach ($envFallbacks as $key => $value) {
            if (!config()->has("app.{$key}")) {
                config()->set("app.{$key}", $value);
            }
        }
    }
}
