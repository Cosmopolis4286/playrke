<?php

namespace App\Http\Requests\ExchangeRate;

use App\Http\Requests\BaseRequest;
use App\Models\ExchangeRate;
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
     * Debe reflejar exactamente los roles que ExchangeRateCrudService::repo()
     * sabe resolver (admin/superadmin/treasurer -> tasa oficial, commerce/
     * customer -> tasa propia) — la ruta en sí no filtra por rol
     * (multiPermission:exchange.view,exchange.manage, asignable a cualquier
     * rol), así que antes un customer o un treasurer con el permiso podían
     * llegar hasta aquí y recibir 403 pese a tener acceso legítimo.
     * 'treasurer' no tiene entidad propia (no está en RoleEntityResolver) —
     * gestiona la tasa OFICIAL igual que admin/superadmin, ver
     * ExchangeRateCrudService::repo().
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['admin', 'superadmin', 'treasurer', 'commerce', 'customer']);
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
             * Valor de la tasa de cambio. gt:0 ya lo cubre BaseModel::
             * setAmountAttribute() (rango 0.0001–999999.9999), pero esa
             * excepción es un InvalidArgumentException genérico que llega al
             * usuario como 500 en vez de 422 — validarlo aquí también da un
             * mensaje claro antes de tocar el modelo.
             */
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999.9999'],

            /**
             * Estatus de la tasa de cambio — Rule::in() contra el enum real
             * de la migración (antes solo 'string': cualquier valor pasaba
             * la validación HTTP y solo fallaba al chocar con el enum de
             * MySQL, como un 500 en vez de un 422 con mensaje claro).
             */
            'status' => ['required', 'string', Rule::in(ExchangeRate::STATUSES)],
        ];
    }

    /**
     * Validaciones que necesitan cruzar datos ya validados (ej. comparar
     * contra el registro existente) — withValidator() corre después de
     * rules(), así que $this->currency_id/amount ya pasaron su propia
     * validación individual acá.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateAmountJump($validator);
        });
    }

    /**
     * Advierte (no bloquea) un salto brusco de tasa respecto al valor
     * vigente — ej. un cero de más convierte 180 en 1800 sin ninguna
     * fricción más allá del modal de confirmación genérico. No lo rechaza
     * como error duro porque una devaluación real puede mover la tasa
     * varias veces en un mismo día; en su lugar, el frontend usa
     * confirm_large_jump=true para pedir una segunda confirmación explícita
     * (ver useExchangeRateForm.ts) — sin ese flag, un salto >50% falla acá.
     */
    private function validateAmountJump($validator): void
    {
        $amount = $this->input('amount');
        $currencyId = $this->input('currency_id');

        if (!$amount || !$currencyId || $this->boolean('confirm_large_jump')) {
            return;
        }

        $current = ExchangeRate::where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->latest('updated_at')
            ->first();

        if (!$current || (float) $current->amount <= 0) {
            return;
        }

        $changePercent = abs(((float) $amount - (float) $current->amount) / (float) $current->amount) * 100;

        if ($changePercent > 50) {
            $validator->errors()->add(
                'amount',
                sprintf(
                    'El nuevo valor difiere %.0f%% del vigente (%s). Si es correcto, confírmalo explícitamente.',
                    $changePercent,
                    $current->amount
                )
            );
        }
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
