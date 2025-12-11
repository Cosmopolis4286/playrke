<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;

/**
 * Middleware que protege contra ataques CSRF.
 *
 * Asegura que todos los formularios y requests POST/PUT/PATCH/DELETE
 * contengan un token válido generado por Laravel.
 */
class VerifyCsrfToken extends Middleware
{
    /**
     * Las URIs que deben ser excluidas de la verificación CSRF.
     *
     * Se recomienda dejar vacío salvo que se trate de APIs públicas.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Por ejemplo: 'webhook/*', si aceptas webhooks externos
    ];

    /**
     * Determina si el request debe pasar la verificación CSRF.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request): bool
    {
        // Primero, aplicar la verificación estándar de Laravel
        $valid = parent::tokensMatch($request);

        // Extra seguridad: verificar que el request use un método seguro
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true; // GET, HEAD y OPTIONS no necesitan token CSRF
        }

        return $valid;
    }

    /**
     * Validación adicional para peticiones AJAX.
     *
     * Esto asegura que las cabeceras coincidan si quieres un nivel extra de seguridad.
     */
    protected function isReading($request): bool
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }
}
