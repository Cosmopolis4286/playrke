<?php

namespace App\Support\Phone;

/**
 * Fuente única de verdad (lado backend) para los patrones de teléfono por
 * país — antes duplicada literalmente en 5 FormRequests distintas
 * (AdminCommerceRequest, AdminCustomerRequest, AdminPlayerRequest,
 * AdminTreasurerRequest, ManagerSellerRequest), cada una con su propia copia
 * de PHONE_PATTERNS que había que mantener sincronizada a mano.
 *
 * Espejo intencional de PHONE_CONFIG en
 * resources/js/composables/form/usePhoneField.ts — no hay forma de compartir
 * código PHP↔TS directamente, así que ambos lados quedan documentados con
 * referencia cruzada; si agregas un país aquí, agrégalo también allá (y
 * viceversa) o el frontend/backend divergen silenciosamente.
 */
final class PhonePatterns
{
    /**
     * Patrones de teléfono por ISO-2, formato E.164-ish con separadores
     * locales (el mismo que produce la máscara de usePhoneField.ts).
     *
     * @var array<string, string>
     */
    private const PATTERNS = [
        'VE' => '/^\+58 0[2-4]\d{2}-\d{7}$/',
        'BR' => '/^\+55 \(\d{2}\) \d{5}-\d{4}$/',
        'CO' => '/^\+57 3\d{2} \d{3} \d{4}$/',
    ];

    /**
     * Regla `regex:...` de Laravel para el país indicado, o null si el país
     * no tiene patrón configurado (sin restricción de formato — mismo
     * comportamiento previo de las 5 requests: un país no soportado no
     * bloquea el guardado, simplemente no valida el formato).
     */
    public static function laravelRule(?string $isoCode2): ?string
    {
        $pattern = self::pattern($isoCode2);

        return $pattern ? "regex:{$pattern}" : null;
    }

    /**
     * Patrón regex crudo (con delimitadores `/`) para el país indicado, o
     * null si no está configurado.
     */
    public static function pattern(?string $isoCode2): ?string
    {
        if (!$isoCode2) {
            return null;
        }

        return self::PATTERNS[strtoupper($isoCode2)] ?? null;
    }

    /** true si el valor cumple el patrón del país; true también si el país no tiene patrón configurado (sin restricción). */
    public static function matches(?string $value, ?string $isoCode2): bool
    {
        $pattern = self::pattern($isoCode2);

        if (!$pattern) {
            return true;
        }

        return is_string($value) && preg_match($pattern, $value) === 1;
    }

    /** Países con patrón de teléfono configurado. */
    public static function supportedCountries(): array
    {
        return array_keys(self::PATTERNS);
    }
}
