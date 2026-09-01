<?php

namespace App\Support;

use RuntimeException;

/**
 * EnvValidator
 *
 * Valida variables de entorno críticas al startup.
 * Si alguna regla falla, lanza RuntimeException antes de que la app sirva tráfico.
 *
 * Uso:
 *   EnvValidator::validate();
 */
class EnvValidator
{
    /**
     * Definición de variables y sus reglas de validación.
     *
     * Reglas disponibles:
     *   required          → no puede estar vacía ni ausente
     *   starts_with:X     → debe comenzar con el prefijo X
     *   ends_with:X       → debe terminar con el sufijo X
     *   in:a,b,c          → debe ser uno de los valores listados
     *   min_length:N      → longitud mínima de N caracteres
     *   url               → debe ser una URL válida (http:// o https://)
     *   numeric           → debe ser numérico
     */
    private static array $rules = [

        // ── Aplicación ────────────────────────────────────────────────
        'APP_KEY'   => ['required', 'starts_with:base64:'],
        'APP_ENV'   => ['required', 'in:local,staging,production,testing'],
        'APP_URL'   => ['required', 'url'],

        // ── Base de datos ──────────────────────────────────────────────
        'DB_CONNECTION' => ['required', 'in:mysql,pgsql,sqlite'],
        'DB_HOST'       => ['required'],
        'DB_PORT'       => ['required', 'numeric'],
        'DB_DATABASE'   => ['required'],
        'DB_USERNAME'   => ['required'],

        // ── Broadcasting (Reverb) ──────────────────────────────────────
        'REVERB_APP_ID'     => ['required'],
        'REVERB_APP_KEY'    => ['required'],
        'REVERB_APP_SECRET' => ['required'],

        // ── Frontend / Vite ────────────────────────────────────────────
        'FRONTEND_URL' => ['required', 'url'],

    ];

    /**
     * Variables que solo se validan en entornos de producción.
     */
    private static array $productionOnly = [
        // 'in:true' (no 'true,false'): en producción la cookie de sesión
        // debe viajar solo por HTTPS — aceptar "false" aquí dejaría pasar
        // un error de configuración que expondría la cookie de sesión por
        // HTTP plano si el edge/proxy no fuerza HTTPS de forma independiente.
        'SESSION_SECURE_COOKIE' => ['required', 'in:true'],
        'LOG_LEVEL'             => ['required', 'in:debug,info,notice,warning,error,critical,alert,emergency'],
        'TURNSTILE_SITE_KEY'    => ['required'],
        'TURNSTILE_SECRET_KEY'  => ['required'],
    ];

    /**
     * Ejecuta todas las validaciones.
     * Lanza RuntimeException con todos los errores encontrados si hay alguno.
     */
    public static function validate(): void
    {
        $errors = [];

        $allRules = self::$rules;

        if (env('APP_ENV') === 'production') {
            $allRules = array_merge($allRules, self::$productionOnly);
        }

        foreach ($allRules as $var => $rules) {
            $value = env($var);

            foreach ($rules as $rule) {
                $error = self::applyRule($var, $value, $rule);
                if ($error !== null) {
                    $errors[] = $error;
                    break; // un error por variable es suficiente
                }
            }
        }

        if (!empty($errors)) {
            $list = implode("\n  - ", $errors);
            throw new RuntimeException(
                "Invalid environment configuration. Fix these variables before starting:\n  - {$list}"
            );
        }
    }

    private static function applyRule(string $var, mixed $value, string $rule): ?string
    {
        if ($rule === 'required') {
            if ($value === null || $value === '') {
                return "{$var}: is required but missing or empty";
            }
            return null;
        }

        // Las demás reglas solo aplican si el valor está presente
        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($rule, 'starts_with:')) {
            $prefix = substr($rule, strlen('starts_with:'));
            if (!str_starts_with((string) $value, $prefix)) {
                return "{$var}: must start with \"{$prefix}\" (got \"{$value}\")";
            }
            return null;
        }

        if (str_starts_with($rule, 'ends_with:')) {
            $suffix = substr($rule, strlen('ends_with:'));
            if (!str_ends_with((string) $value, $suffix)) {
                return "{$var}: must end with \"{$suffix}\" (got \"{$value}\")";
            }
            return null;
        }

        if (str_starts_with($rule, 'in:')) {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array((string) $value, $allowed, true)) {
                $options = implode(', ', $allowed);
                return "{$var}: must be one of [{$options}] (got \"{$value}\")";
            }
            return null;
        }

        if (str_starts_with($rule, 'min_length:')) {
            $min = (int) substr($rule, strlen('min_length:'));
            if (strlen((string) $value) < $min) {
                return "{$var}: must be at least {$min} characters long";
            }
            return null;
        }

        if ($rule === 'url') {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return "{$var}: must be a valid URL (got \"{$value}\")";
            }
            return null;
        }

        if ($rule === 'numeric') {
            if (!is_numeric($value)) {
                return "{$var}: must be numeric (got \"{$value}\")";
            }
            return null;
        }

        return null;
    }
}
