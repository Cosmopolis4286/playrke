<?php

namespace App\Http\Middleware;

use App\Services\Token\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateToken
{
    public function __construct(
        protected TokenService $tokenService,
    ) {
    }

    /**
     * Maneja la validación del token.
     *
     * Soporta dos modos:
     * - 'required': El token es obligatorio (por defecto)
     * - 'optional': El token es opcional, si se proporciona se valida
     *
     * @param string $mode 'required' o 'optional'
     */
    public function handle(Request $request, Closure $next, string $mode = 'required') : Response
    {
        $rawToken = $request->query('token');

        $token = $mode === 'optional'
            ? $this->tokenService->validateTokenOptional($rawToken)
            : $this->tokenService->validateToken($rawToken);

        // Guardamos el token validado para uso posterior
        $request->attributes->set('token', $token);

        return $next($request);
    }
}
