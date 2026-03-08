<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Middleware principal de Inertia.
 *
 * Responsabilidades:
 * - Definir el root view que Inertia usará como HTML base
 * - Gestionar versionado de assets
 * - Compartir props globales con la SPA
 *
 * Este middleware se ejecuta en cada request Inertia.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * Vista raíz por defecto de Inertia.
     *
     * Se utiliza cuando no se sobreescribe dinámicamente
     * mediante el método rootView().
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determina dinámicamente qué vista Blade será usada
     * como root view para Inertia.
     *
     * Permite coexistir:
     * - Blade tradicional (AdminLTE, layouts clásicos)
     * - Inertia SPA (layout mínimo, sin dependencias legacy)
     *
     * La decisión se basa en un flag definido en la ruta.
     *
     * @param Request $request
     * @return string
     */
    public function rootView(Request $request): string
    {
        return ($request->route()?->defaults['inertiaOnly'] ?? false)
            ? 'components.layouts.inertia'
            : $this->rootView;
    }

    /**
     * Determina la versión actual de los assets.
     *
     * Se utiliza para forzar recarga automática del frontend
     * cuando cambian los archivos compilados (Vite).
     *
     * @param Request $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props compartidas globalmente con todas las vistas Inertia.
     *
     * Ideal para:
     * - Usuario autenticado
     * - Permisos
     * - Flags globales
     * - Configuración de la app
     *
     * ⚠️ Evitar lógica pesada aquí.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'app' => [
                'env'   => app()->environment(),
                'debug' => config('app.debug'),
            ],
        ];
    }
}
