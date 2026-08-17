<?php

namespace App\Rules;

use App\Models\Country;
use App\Support\Phone\PhonePatterns;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida que un número de teléfono cumpla el formato esperado para el país
 * indicado (ver App\Support\Phone\PhonePatterns) — reemplaza la lógica
 * `phoneRegex()` que estaba duplicada en 5 FormRequests distintas.
 *
 * Uso típico (país referenciado por otro campo del mismo request, ej. 'country'):
 *
 *   'phone' => ['required', 'string', 'max:20', new ValidPhoneForCountry($this->input('country'))],
 *
 * O pasando directamente el ISO-2 si ya se conoce:
 *
 *   'phone' => ['required', new ValidPhoneForCountry(isoCode2: 'VE')],
 *
 * Un país sin patrón configurado (ver PhonePatterns::supportedCountries())
 * no bloquea el valor — mismo comportamiento que el phoneRegex() original,
 * para no romper países aún no soportados por el diccionario de patrones.
 */
final class ValidPhoneForCountry implements ValidationRule
{
    private readonly ?string $isoCode2;

    /**
     * @param string|null $countryId  ID del país (tabla countries) — se resuelve a iso_code_2 internamente.
     * @param string|null $isoCode2   ISO-2 directo, si ya se conoce (evita la consulta a countries). Tiene prioridad sobre $countryId.
     */
    public function __construct(?string $countryId = null, ?string $isoCode2 = null)
    {
        $this->isoCode2 = $isoCode2 ?? ($countryId ? Country::where('id', $countryId)->value('iso_code_2') : null);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!PhonePatterns::matches($value, $this->isoCode2)) {
            $fail('El formato del teléfono no es válido para el país seleccionado.');
        }
    }
}
