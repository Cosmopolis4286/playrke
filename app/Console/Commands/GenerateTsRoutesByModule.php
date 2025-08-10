<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Filesystem\Filesystem;

class GenerateTsRoutesByModule extends Command
{
    protected $signature = 'make:ts-routes-modules';
    protected $description = 'Genera archivos TS separados por módulo (prefix) con rutas y función route()';

    public function handle()
    {
        $routes = collect(Route::getRoutes());
        $fs = new Filesystem();

        // Define módulos solo con prefijos
        $modules = [
            'admin',
            'bingo',
            'commerce',
            'exchangeRate',
            'info',
            'report',
            'rewards',
            'pix',
            'seller'
        ];

        foreach ($modules as $prefix) {
            $varName = $prefix . 'Routes'; // construye nombre variable/archivo

            // Filtra rutas que empiecen con el prefijo o sean exactamente igual al prefijo
            $filtered = $routes
                ->filter(fn($route) => $this->uriStartsWithPrefix($route->uri(), $prefix))
                ->mapWithKeys(fn($route) => $this->mapRoute($route, $prefix))
                ->toArray();

            if (empty($filtered)) {
                $this->warn("No se encontraron rutas para el módulo '{$prefix}'");
                continue;
            }

            $content = $this->generateTsFileContent($varName, $filtered);

            $filePath = base_path("resources/js/routes/{$varName}.ts");
            $fs->ensureDirectoryExists(dirname($filePath));
            file_put_contents($filePath, $content);

            $this->info("Archivo generado: {$filePath}");
        }
    }


    /**
     * Determina si la URI inicia con el prefijo dado o es exactamente igual.
     */
    protected function uriStartsWithPrefix(string $uri, string $prefix): bool
    {
        return $uri === $prefix || str_starts_with($uri, "{$prefix}/");
    }

    /**
     * Mapea una ruta Laravel a la estructura [nombre => url].
     * Limpia el nombre eliminando el prefijo si es necesario.
     */
    protected function mapRoute($route, string $prefix): array
    {
        $name = $route->getName();

        if (!$name) {
            // Genera un nombre a partir de la URI (reemplaza / por _)
            $name = str_replace('/', '_', $route->uri());
        } elseif (str_starts_with($name, "{$prefix}.")) {
            // Quita el prefijo y el punto
            $name = substr($name, strlen($prefix) + 1);
        }

        return [
            $name => '/' . ltrim($route->uri(), '/'),
        ];
    }

    /**
     * Genera el contenido TypeScript para el archivo de rutas.
     */
    protected function generateTsFileContent(string $varName, array $routes): string
    {
        $json = json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $date = (new \DateTime('now', new \DateTimeZone('GMT')))->format('Y-m-d\TH:i:s\Z');

        return <<<TS
        // ======================================================
        // Archivo: resources/js/routes/{$varName}.ts
        // Descripción: Rutas del módulo {$varName}
        // Fecha de actualización (GMT): {$date}
        // Generado automáticamente con make:ts-routes-modules
        // ======================================================

        export const {$varName} = {$json} as const;

        /**
         * Obtiene la URL para la ruta dada con parámetros opcionales.
         * @param name Nombre de la ruta (clave del objeto)
         * @param params Parámetros para reemplazar en la URL (por ejemplo {id})
         * @returns URL con parámetros reemplazados
         */
        export function route<K extends keyof typeof {$varName}>(
            name: K,
            params?: Record<string, string | number>
        ): string {
            let url = {$varName}[name] as string;

            if (params) {
                for (const [key, value] of Object.entries(params)) {
                    url = url.replace(new RegExp('\\{' + key + '\\}', 'g'), String(value));
                }
            }

            return url;
        }
        TS;
    }
}
