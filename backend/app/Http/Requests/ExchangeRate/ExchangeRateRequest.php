<?php

namespace App\Http\Requests\ExchangeRate;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;
use Illuminate\Validation\Rule;

/**
 * Request para validar datos de una Tasa de Cambio (ExchangeRate).
 *
 * Se utiliza tanto para creación como actualización.
 *
 * Payload esperado:
 * {
 *   "currency_id": "USD",
 *   "amount": 1.25,
 *   "token": "customer"
 *   "status": "ACTIVE"
 * }
 */
class ExchangeRateRequest extends BaseRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['admin', 'superadmin', 'commerce']);
    }

    /**
     * Reglas de validación dinámicas según:
     * - create / update
     * - valor del campo "token"
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isUpdate = $this->isUpdate();
        $token = $this->input('token');

        /**
         * Reglas base para la moneda.
         */
        $currencyRules = [
            $isUpdate ? 'sometimes' : 'required',
            'string',
            'size:3',
        ];

        /**
         * Si el token es distinto de admin,
         * la moneda debe existir en la tabla currencies.
         */
        if ($token !== 'admin') {
            $currencyRules[] = 'exists:currencies,iso_code';
        }

        /**
         * Para creación, verificar que no exista una tasa de cambio para esta moneda
         */
        if (!$isUpdate) {
            $currencyRules[] = function ($attribute, $value, $fail) {
                $exists = \App\Models\ExchangeRate::where('currency_id', $value)->exists();
                if ($exists) {
                    $fail('Ya existe una tasa de cambio registrada para esta moneda.');
                }
            };
        }

        return [
            /**
             * Rol de usuario al que aplica la tasa de cambio.
             */
            'token' => [
                'sometimes',
                'string',
                Rule::exists('public_tokens', 'token_hash')->where(function ($query) {
                    $query->where('status', 'ACTIVE');
                }),
            ],

            /**
             * Código de moneda ISO 4217 (ej: USD, EUR).
             */
            'currency_id' => $currencyRules,

            /**
             * Valor de la tasa de cambio.
             */
            'amount' => ['required', 'numeric', 'gt:0'],

            /**
             * Estatus de la tasa de cambio
             */
            'status' => ['required', 'string']
        ];
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.exists' => 'El token no es válido o está inactivo.',

            'currency_id.required' => 'La moneda es obligatoria.',
            'currency_id.size' => 'La moneda debe tener exactamente 3 caracteres.',
            'currency_id.exists' => 'La moneda seleccionada no es válida.',

            'amount.required' => 'El valor de cambio es obligatorio.',
            'amount.numeric' => 'El valor de cambio debe ser numérico.',
            'amount.gt' => 'El valor de cambio debe ser mayor a cero.',

            'status.required' => 'El status es obligatorio.',
        ];
    }
}
