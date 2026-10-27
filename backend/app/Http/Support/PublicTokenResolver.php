<?php

namespace App\Http\Support;

use Illuminate\Http\Request;

class PublicTokenResolver
{
    /**
     * Obtiene el token público desde el request (si existe).
     *
     * En rutas públicas el middleware valida el token y lo guarda en attributes.
     * Si hay public_token significa que el token fue validado correctamente.
     */
    public function resolve(Request $request) : ?string
    {
        // El middleware ya validó el token y marcó el request
        if ($request->attributes->has('public_token')) {
            return $request->query('token');
        }

        return null;
    }
}
