<?php

namespace App\Http\Middleware;

use App\Models\PublicToken;
use Closure;
use Illuminate\Http\Request;

class PublicTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $scope)
    {
        // Si hay sesión activa, no aplica token público
        if (auth()->guard()->check()) {
            return $next($request);
        }

        // Token por query (iframe-friendly)
        $token = $request->query('token');

        if (! $token || ! is_string($token)) {
            abort(401, 'Public token requerido');
        }

        // Hash constante
        $tokenHash = hash('sha256', $token);

        $publicToken = PublicToken::valid()
            ->where('token_hash', $tokenHash)
            ->whereJsonContains('scopes', $scope)
            ->with('user')
            ->first();

        if (! $publicToken || ! $publicToken->user) {
            abort(403, 'Public token inválido o expirado');
        }

        // Actualizar último uso (sin romper performance)
        $publicToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        // Inyectar usuario público al request
        $request->attributes->set('public_user', $publicToken->user);
        $request->attributes->set('public_token', $publicToken);

        return $next($request);
    }
}
