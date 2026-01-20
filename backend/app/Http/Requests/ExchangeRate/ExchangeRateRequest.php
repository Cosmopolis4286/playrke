<?php

namespace App\Http\Requests\ExchangeRate;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para validar datos de una Tasa de Cambio (ExchangeRate).
 *
 * Se utiliza tanto para creación como actualización.
 *
 * Payload esperado:
 * {
 *   "currency": "USD",
 *   "exchange_rate": 1.25,
 *   "token?": "customer"
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
        return PermissionService::checkPrefix(['admin', 'commerce']);
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
         * Si el token NO es commerce ni customer,
         * la moneda debe existir en la tabla currencies.
         */
        $currencyRules = [
            $isUpdate ? 'sometimes' : 'required',
            'string',
            'size:3',
        ];

        if ($token) {
            $currencyRules[] = 'exists:currencies,code';
            'token' => [
                'string',
                'in:admin,commerce,customer',
            ],
        }

        return [
            /**
             * Rol de usuario al que aplica la tasa de cambio.
             */


            /**
             * Código de moneda ISO 4217 (ej: USD, EUR).
             */
            'currency' => $currencyRules,

            /**
             * Valor de la tasa de cambio.
             */
            'exchange_rate' => [
                $isUpdate ? 'sometimes' : 'required',
                'numeric',
                'gt:0',
            ],
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
            'token.required' => 'El token es obligatorio.',
            'token.in' => 'El token no es válido.',

            'currency.required' => 'La moneda es obligatoria.',
            'currency.size' => 'La moneda debe tener exactamente 3 caracteres.',
            'currency.exists' => 'La moneda seleccionada no es válida.',

            'exchange_rate.required' => 'La tasa de cambio es obligatoria.',
            'exchange_rate.numeric' => 'La tasa de cambio debe ser numérica.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor a cero.',
        ];
    }
}
