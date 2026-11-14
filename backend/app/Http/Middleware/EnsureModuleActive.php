<?php

namespace App\Http\Middleware;

use App\Models\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * module:<key> — bloquea las rutas de un módulo apagado desde el panel de
 * módulos (superadmin). Antes apagar un módulo solo lo quitaba del menú y
 * sus rutas seguían accesibles por URL.
 *
 * Admin y superadmin pasan igual: pueden configurar y probar un módulo
 * (p. ej. partidas internas de Trivia) antes de abrirlo a los jugadores.
 */
class EnsureModuleActive
{
    private const STAFF_ROLES = ['superadmin', 'admin'];

    public function handle(Request $request, Closure $next, string $moduleKey) : Response
    {
        if (Module::isActive($moduleKey) || $request->user()?->hasRole(self::STAFF_ROLES)) {
            return $next($request);
        }

        $message = 'Esta sección no está disponible por ahora.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 404);
        }

        abort(404, $message);
    }
}
