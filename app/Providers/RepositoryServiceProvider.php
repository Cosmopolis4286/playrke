<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use App\Repositories\Settings\Contracts\SettingRepositoryContract;
use App\Repositories\Settings\SettingRepository;
use App\Models\Settings\Setting;

/**
 * RepositoryServiceProvider
 *
 * Este proveedor se encarga de:
 *  1. Registrar manualmente los repositorios críticos con inyección de dependencias
 *  2. Registrar automáticamente el resto de repositorios que implementen contratos
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios en el contenedor de Laravel
     *
     * @return void
     */
    public function register(): void
    {
        // ---------------------------------------------------------
        // 1. Repositorios críticos con inyección de dependencias
        // ---------------------------------------------------------
        $this->app->bind(SettingRepositoryContract::class, function ($app) {
            // Inyectamos el modelo Setting en el repositorio
            return new SettingRepository($app->make(Setting::class));
        });

        // ---------------------------------------------------------
        // 2. Registro automático del resto de repositorios
        // ---------------------------------------------------------
        $this->registerRepositoriesAutomatically(app_path('Repositories'));
    }

    /**
     * Registro automático de repositorios que implementen contratos
     *
     * Escanea un directorio de repositorios y vincula
     * automáticamente cualquier clase que implemente un contrato.
     *
     * @param string $basePath
     * @return void
     */
    private function registerRepositoriesAutomatically(string $basePath): void
    {
        $filesystem = new Filesystem();

        foreach ($filesystem->allFiles($basePath) as $file) {
            $namespace = $this->getClassNamespace($file->getPathname());
            $className = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            // Saltar interfaces y clases abstractas
            if ($reflection->isInterface() || $reflection->isAbstract()) {
                continue;
            }

            // Detectar contratos implementados
            foreach ($reflection->getInterfaces() as $interface) {
                if (str_contains($interface->getName(), 'Contract')) {
                    // Evitar volver a registrar SettingRepository
                    if ($interface->getName() === SettingRepositoryContract::class) {
                        continue;
                    }

                    // Vinculación automática: interfaz => implementación
                    $this->app->bind($interface->getName(), $className);
                }
            }
        }
    }

    /**
     * Obtener el namespace de una clase desde un archivo PHP
     *
     * @param string $file
     * @return string
     */
    private function getClassNamespace(string $file): string
    {
        $src = file_get_contents($file);

        if (preg_match('/^namespace\s+(.+?);/m', $src, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Inicializar servicios después del registro
     *
     * @return void
     */
    public function boot(): void
    {
        // Por ahora no se requiere lógica en boot()
    }
}
