<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Filesystem\Filesystem;

class GenerateTsRoutesByModule extends Command
{
    protected $signature = 'make:ts-routes-modules';
    protected $description = 'Genera archivos TS separados por módulo (prefix) con rutas';

    /** @var Filesystem */
    protected Filesystem $fs;

    /** Lista de módulos/prefijos a generar */
    protected array $modules = [
        'admin',
        'auth',
        'api',
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
        'payment_accounts_co',
        'pix',
        'profile',
        'seller',
        'treasurer',
        'wallet',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->fs = new Filesystem();
    }

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        $routes = collect(Route::getRoutes());

        foreach ($this->modules as $prefix) {
            $varName = $this->kebabToCamelCase($prefix) . 'Routes';

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
            $this->fs->ensureDirectoryExists(dirname($filePath));
            $this->fs->put($filePath, $content);

            $this->info("Archivo generado: {$filePath}");
        }
    }

    /**
     * Convierte un string kebab-case a camelCase.
     *
     * @param string $string
     * @return string
     */
    protected function kebabToCamelCase(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $string))));
    }

    /**
     * Convierte snake_case o kebab-case a camelCase.
     *
     * @param string $string
     * @return string
     */
    protected function toCamelCase(string $string): string
    {
        $string = str_replace(['-', '_'], ' ', $string);
        return lcfirst(str_replace(' ', '', ucwords($string)));
    }

    /**
     * Verifica si la URI comienza con el prefijo o es igual.
     *
     * @param string $uri
     * @param string $prefix
     * @return bool
     */
    protected function uriStartsWithPrefix(string $uri, string $prefix): bool
    {
        return $uri === $prefix || str_starts_with($uri, "{$prefix}/");
    }

    /**
     * Mapea una ruta Laravel a un arreglo [nombre => url].
     * Quita el prefijo del nombre si corresponde y convierte a camelCase.
     *
     * @param \Illuminate\Routing\Route $route
     * @param string $prefix
     * @return array<string, string>
     */
    protected function mapRoute($route, string $prefix): array
    {
        $name = $route->getName();

        if (!$name) {
            // Si no hay nombre, generar uno desde URI, pero quitando prefijo y slash
            $uri = $route->uri();
            if ($this->uriStartsWithPrefix($uri, $prefix)) {
                $namePart = ltrim(substr($uri, strlen($prefix)), '/');
            } else {
                $namePart = $uri;
            }
            $namePart = str_replace(['/', '-', '_'], ' ', $namePart);
            $name = $this->toCamelCase($namePart);
        } else {
            // Si tiene nombre, quitar prefijo y convertir a camelCase
            if (str_starts_with($name, "{$prefix}.")) {
                $name = substr($name, strlen($prefix) + 1);
            }
            $name = $this->toCamelCase(str_replace('.', ' ', $name));
        }

        // Retornar key camelCase y valor URI original con slash inicial
        return [$name => '/' . ltrim($route->uri(), '/')];
    }

    /**
     * Genera el contenido del archivo TS con solo la constante exportada,
     * con claves sin comillas y valores entre comillas dobles.
     *
     * @param string $varName
     * @param array<string, string> $routes
     * @return string
     */
    protected function generateTsFileContent(string $varName, array $routes): string
    {
        $lines = [];
        foreach ($routes as $key => $value) {
            // Escapa comillas dobles dentro del valor si existieran (normalmente no)
            $escapedValue = addslashes($value);
            $lines[] = "  {$key}: \"{$escapedValue}\",";
        }
        $objectBody = implode("\n", $lines);

        $date = (new \DateTime('now', new \DateTimeZone('GMT')))->format('Y-m-d\TH:i:s\Z');

        return <<<TS
        // ======================================================
        // Archivo: resources/js/routes/{$varName}.ts
        // Descripción: Rutas del módulo {$varName}
        // Fecha de actualización (GMT): {$date}
        // Generado automáticamente con make:ts-routes-modules
        // ======================================================

        export const {$varName} = {
        {$objectBody}
        } as const;

        TS;
    }
}
