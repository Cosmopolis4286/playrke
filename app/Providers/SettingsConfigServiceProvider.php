<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Settings\SettingRepository;
use Illuminate\Support\Facades\Log;

class SettingsConfigServiceProvider extends ServiceProvider
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
            $settingRepo = app(SettingRepository::class);

            // Obtenemos todos los settings activos
            $allSettings = $settingRepo->getActiveSetting();

            foreach ($allSettings as $setting) {
                // Convertimos el parámetro a minúsculas y snake_case
                $param = strtolower($setting->param);

                // Solo seteamos si no existe en config
                if (!config()->has("app.{$param}")) {
                    config()->set("app.{$param}", $setting->getFormattedValue());
                }
            }
        } catch (\Throwable $e) {
            // Opcional: log del error para debugging
            Log::error('Error al cargar SettingsConfig: ' . $e->getMessage());
        }

        // Valores de fallback desde .env
        $envFallbacks = [
            'name' => env('APP_NAME', 'Laravel'),
            'env' => env('APP_ENV', 'production'),
            'debug' => (bool) env('APP_DEBUG', false),
            'url' => env('APP_URL', 'http://localhost'),
            'timezone' => env('APP_TIMEZONE', 'America/Caracas'),
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
