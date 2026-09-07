<?php

namespace App\Http\Middleware;

use App\Services\TosService;
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
    public function __construct(private readonly TosService $tosService) {}
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
        $user = $request->user();
        
        // Cargar relaciones si el usuario está autenticado.
        // timeZone se precarga aquí (aunque no se usa en este share()) para que
        // SetUserTimezone, que corre después en el stack de 'web', la reutilice
        // sin disparar una query lazy-load adicional en cada request.
        if ($user) {
            $user->load(['roles', 'timeZone']);
        }
        
        return [
            ...parent::share($request),

            'app' => [
                'env'   => app()->environment(),
                'debug' => config('app.debug'),
            ],
            
            'auth' => [
                'user' => $user ? [
                    'id'                  => $user->id,
                    'name'                => $user->name,
                    'email'               => $user->email,
                    'avatar_url'          => $user->profile_photo_url ?: asset('img/fav.ico'),
                    'kyc_level'           => (int) ($user->kyc_level ?? 0),
                    'kyc_status'          => $user->kyc_status ?? 'NONE',
                    'has_withdrawal_pin'  => $user->hasWithdrawalPin(),
                    'tos_needs_acceptance'=> $this->tosService->needsAcceptance($user),
                    'userRoles'           => $user->roles ? $user->roles->map(fn($role) => [
                        'role' => [
                            'name'   => $role->name,
                            'prefix' => $role->prefix,
                        ],
                    ]) : [],
                ] : null,
            ],
        ];
    }
}
