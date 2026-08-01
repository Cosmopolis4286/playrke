<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use App\Contracts\SettingRepositoryInterface;
use App\Repositories\SettingRepository;
use App\Models\Setting;
use App\Contracts\RoleEntityRepositoryInterface;
use App\Contracts\GameCatalogRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Interfaces base compartidas por varias interfaces específicas
     * (ej. AdminRepositoryInterface/CommerceRepositoryInterface extienden
     * RoleEntityRepositoryInterface). El auto-scan de abajo captura TODAS
     * las interfaces de getInterfaces(), incluidas las heredadas, así que
     * sin esta exclusión terminarían bindeadas a la primera clase concreta
     * que el filesystem devuelva (orden no determinista) en vez de quedar
     * sin binding directo, ya que nadie debe inyectarlas por sí solas.
     */
    private const EXCLUDED_BASE_INTERFACES = [
        RoleEntityRepositoryInterface::class,
        GameCatalogRepositoryInterface::class,
    ];

    public function register(): void
    {
        // Repositorio crítico con dependencias explícitas
        $this->app->singleton(SettingRepositoryInterface::class, function ($app) {
            return new SettingRepository($app->make(Setting::class));
        });

        // Registro automático de repositories
        $this->registerRepositoriesAutomatically(app_path('Repositories'));
    }

    private function registerRepositoriesAutomatically(string $basePath): void
    {
        $filesystem = new Filesystem();

        foreach ($filesystem->allFiles($basePath) as $file) {

            $namespace = $this->getClassNamespace($file->getPathname());

            if (!$namespace) {
                continue;
            }

            $className = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            foreach ($reflection->getInterfaces() as $interface) {

                // SOLO RepositoryInterfaces
                if (!str_ends_with($interface->getShortName(), 'RepositoryInterface')) {
                    continue;
                }

                // Evitar doble binding explícito
                if ($interface->getName() === SettingRepositoryInterface::class) {
                    continue;
                }

                // Interfaces base compartidas: no bindear directamente
                if (in_array($interface->getName(), self::EXCLUDED_BASE_INTERFACES, true)) {
                    continue;
                }

                // Evitar sobrescribir bindings ya registrados
                if ($this->app->bound($interface->getName())) {
                    continue;
                }

                $this->app->singleton(
                    $interface->getName(),
                    $className
                );
            }
        }
    }

    private function getClassNamespace(string $file): string
    {
        $src = file_get_contents($file);

        if (preg_match('/^namespace\s+(.+?);/m', $src, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
