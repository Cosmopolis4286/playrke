<?php

namespace App\Http\Middleware;

use App\Services\Token\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateToken
{
    public function __construct(
        protected TokenService $tokenService
    ) {}

    /**
     * Maneja la validación del token.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->tokenService->validateToken(
            $request->query('token')
        );

        // Guardamos el token validado para uso posterior
        $request->attributes->set('token', $token);

        return $next($request);
    }
}
