<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Valida el token de Cloudflare Turnstile (captcha invisible) contra el
 * endpoint siteverify de Cloudflare — segunda barrera además del throttle
 * de Laravel: un botnet distribuido en muchas IPs evade el rate limiting
 * por IP, pero no resuelve el challenge de Turnstile.
 *
 * Uso: 'cf-turnstile-response' => ['required', new CloudflareTurnstile($request->ip())],
 *
 * Se desactiva sola si TURNSTILE_SECRET_KEY no está configurada (dev/local
 * sin cuenta Cloudflare) — falla abierto en ese caso para no bloquear
 * desarrollo local; en producción la clave es obligatoria (ver
 * App\Support\EnvValidator).
 */
final class CloudflareTurnstile implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly ?string $remoteIp = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail) : void
    {
        $secret = config('services.turnstile.secret_key');

        if (! $secret) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Verificación de seguridad requerida.');

            return;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret' => $secret,
                'response' => $value,
                'remoteip' => $this->remoteIp,
            ]);

            if (! $response->successful() || ! ($response->json('success') === true)) {
                $fail('Verificación de seguridad fallida. Intenta de nuevo.');
            }
        } catch (Throwable $e) {
            Log::warning('CloudflareTurnstile: fallo al verificar token', ['error' => $e->getMessage()]);
            $fail('No se pudo completar la verificación de seguridad. Intenta de nuevo.');
        }
    }
}
