<?php
// app/Helpers/security.php

if (!function_exists('csp_nonce')) {
    /**
     * Nonce único por request para permitir <script> inline bajo la CSP (ver
     * App\Http\Middleware\SecurityHeaders, que genera el mismo valor y lo
     * comparte en $request->attributes ANTES de que cualquier vista Blade se
     * renderice). Usar como: <script nonce="{{ csp_nonce() }}">.
     *
     * Fuera de un request HTTP (comandos artisan, tests) devuelve '' — no
     * hay CSP que satisfacer ahí.
     */
    function csp_nonce(): string
    {
        $request = request();

        return $request?->attributes->get('csp_nonce', '') ?? '';
    }
}
