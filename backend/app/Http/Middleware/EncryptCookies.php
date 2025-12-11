<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

/**
 * Middleware para encriptar cookies de la aplicación.
 *
 * Protege la información almacenada en cookies asegurando
 * que no pueda ser leída directamente por el cliente.
 */
class EncryptCookies extends Middleware
{
    /**
     * Las cookies que no deben ser encriptadas.
     *
     * IMPORTANTE: XSRF-TOKEN no debe encriptarse para que el frontend
     * pueda leerla y enviarla en el header X-XSRF-TOKEN.
     *
     * @var array<int, string>
     */
    protected $except = [
        'XSRF-TOKEN',
    ];
}
