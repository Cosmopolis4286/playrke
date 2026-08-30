<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad HTTP — antes no se enviaba ninguna (clickjacking,
 * MIME sniffing, XSS sin mitigar por CSP). Cloudflare (WAF/edge) añade su
 * propia protección delante, pero estas cabeceras son responsabilidad de la
 * aplicación y deben viajar igual si el request llegase directo a Traefik.
 *
 * El nonce se genera ANTES de $next($request) para que esté disponible vía
 * csp_nonce() (app/Helpers/security.php) mientras Blade renderiza la vista
 * — de ahí no poder resolverlo desde el objeto Response.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $headerName = config('security.csp.enforce')
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        $response->headers->set($headerName, $this->buildPolicy($nonce));

        return $response;
    }

    /**
     * Arma la política a partir del inventario real de fuentes que la app
     * carga hoy (ver auditoría: vendor JS/CSS todo local vía asset(), Google
     * Fonts por @import en styles.css, Reverb por WebSocket, Turnstile de
     * Cloudflare para el captcha, y Vite dev server/HMR solo fuera de prod).
     */
    private function buildPolicy(string $nonce): string
    {
        $isProduction = app()->environment('production');
        $reverbHost   = $this->reverbConnectSrc();

        $scriptSrc = ["'self'", "'nonce-{$nonce}'", 'https://challenges.cloudflare.com'];
        $connectSrc = ["'self'", 'https://challenges.cloudflare.com', $reverbHost];

        if (!$isProduction) {
            // Vite dev server + HMR (ver vite.config.ts: server.host/port).
            $scriptSrc[]  = "'unsafe-eval'"; // HMR de Vite inyecta eval() en dev
            $scriptSrc[]  = 'http://localhost:5173';
            $connectSrc[] = 'http://localhost:5173';
            $connectSrc[] = 'ws://localhost:5173';
        }

        $directives = [
            "default-src 'self'",
            'script-src ' . implode(' ', $scriptSrc),
            // 'unsafe-inline' aquí: Bootstrap/AdminLTE y los <style scoped> de
            // Vue inyectan <style> inline; separar esto con nonce exigiría
            // tocar cientos de componentes .vue existentes para un beneficio
            // marginal (los ataques de "CSS injection" son mucho menos
            // comunes/graves que script injection, que sí exige nonce arriba).
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: blob:",
            'connect-src ' . implode(' ', $connectSrc),
            'frame-src https://challenges.cloudflare.com',
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        return implode('; ', $directives);
    }

    private function reverbConnectSrc(): string
    {
        // OJO: REVERB_BROADCAST_HOST (broadcasting.connections.reverb.options.host)
        // es el host INTERNO que Laravel usa server-side para hablar con
        // Reverb dentro de la red Docker (ver .env.dev: 'reverb', el nombre
        // del contenedor) — el navegador nunca lo toca. env('REVERB_HOST')
        // es el que realmente usa el cliente (resources/js/plugins/echo.ts),
        // y por tanto el correcto para esta CSP.
        $host = env('REVERB_HOST') ?? parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        return "wss://{$host} ws://{$host}";
    }
}
