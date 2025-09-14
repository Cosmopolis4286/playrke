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
            'binance',
            'bingo',
            'bingo-automatic-room',
            'bingocash',
            'bingo-patterns',
            'commerce',
            'customer',
            'directory',
            'exchange-rate',
            'games',
            'iframe',
            'image',
            'info',
            'report',
            'rewards',
            'other-treasurers',
            'pagomovil',
            'payment',
            'payment-method-col',
            'pix',
            'profile',
            'seller',
            'treasurer',
            'wallet'
        ];

        foreach ($modules as $prefix) {
            // Construye nombre variable/archivo
            $varName = $this->kebabToCamelCase($prefix) . 'Routes';

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
     * Convierte un string en formato kebab-case (con guiones) a camelCase.
     *
     * Ejemplo:
     *   'exchange-rate' => 'exchangeRate'
     *   'some-module-name' => 'someModuleName'
     */
    protected function kebabToCamelCase(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $string))));
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
     * Genera el contenido TypeScript para el archivo de rutas, incluyendo `getRoute`.
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

        import { route as baseRoute } from './helper.js';

        export const {$varName} = {$json} as const;

        /**
         * Retorna la URL de la ruta especificada usando las rutas del módulo.
         */
        export function route(name, params) {
            return baseRoute({$varName}, name, params);
        }

        /**
         * Retorna la URL de una ruta específica dentro de {$varName}.
         * Reemplaza los placeholders con los parámetros proporcionados.
         * Elimina los opcionales que no tengan valor.
         */
        export function getRoute(name, params) {
            let url = {$varName}[name];

            if (params) {
                Object.entries(params).forEach(([key, value]) => {
                    url = url.replace(new RegExp("{"+key+"\\??}", "g"), String(value));
                });
            }

            // Elimina cualquier placeholder opcional que no tenga valor
            url = url.replace(/\/{[^}]+\?}/g, "");

            return url;
        }
        TS;
    }
}
