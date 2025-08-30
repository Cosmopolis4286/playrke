<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

/**
 * Middleware que elimina espacios en blanco al inicio y al final
 * de los valores de los inputs en las peticiones HTTP.
 *
 * Esto ayuda a evitar errores en validaciones y búsquedas
 * cuando el usuario introduce espacios innecesarios.
 */
class TrimStrings extends Middleware
{
    /**
     * Los atributos que NO deben ser recortados.
     *
     * Normalmente, contraseñas u otros campos sensibles
     * se mantienen exactamente como fueron introducidos.
     *
     * @var array<int, string>
     */
    protected $except = [
        'password',
        'password_confirmation',
    ];
}
