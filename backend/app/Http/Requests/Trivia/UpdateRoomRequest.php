<?php

declare(strict_types=1);

namespace App\Http\Requests\Trivia;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'              => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'category'           => ['nullable', 'string', 'max:50'],
            'max_players'        => ['required', 'integer', 'min:2', 'max:500'],
            'entry_fee'          => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'entry_currency_id'  => ['nullable', 'string', 'exists:currencies,id'],
            'fee_by_currency'                => ['nullable', 'array'],
            'fee_by_currency.*'              => ['numeric', 'min:0.01', 'max:999999.99'],
            'prize_type'         => ['nullable', 'in:fixed,accumulated'],
            'prize_amount'       => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'prize_currency_id'  => ['nullable', 'string', 'exists:currencies,id'],
            'scheduled_at'       => ['nullable', 'date', 'after:now'],
            'settings'           => ['nullable', 'array'],
            'settings.allow_powerups'   => ['nullable', 'boolean'],
            'settings.show_leaderboard' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede exceder 255 caracteres.',
            'description.max' => 'La descripción no puede exceder 1000 caracteres.',
            'category.max' => 'La categoría no puede exceder 50 caracteres.',
            'max_players.required' => 'Debes indicar el máximo de jugadores.',
            'max_players.min' => 'La sala debe permitir al menos 2 jugadores.',
            'max_players.max' => 'Máximo 500 jugadores permitidos.',
            'entry_currency_id.exists' => 'La moneda de entrada seleccionada no es válida.',
            'fee_by_currency.*.numeric' => 'El precio de entrada debe ser un número.',
            'fee_by_currency.*.min' => 'El precio de entrada debe ser mayor a 0.',
            'prize_type.in' => 'El tipo de premio debe ser "fijo" o "acumulado".',
            'prize_amount.numeric' => 'El monto del premio debe ser un número.',
            'prize_amount.min' => 'El monto del premio no puede ser negativo.',
            'prize_currency_id.exists' => 'La moneda del premio seleccionada no es válida.',
            'scheduled_at.date' => 'La fecha de inicio no es válida.',
            'scheduled_at.after' => 'La fecha y hora de inicio debe ser futura.',
        ];
    }

    /**
     * Validaciones adicionales que dependen de la combinación de varios campos.
     */
    public function withValidator(ValidatorContract|Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $feeByCurrency = collect($this->input('fee_by_currency', []))
                ->filter(fn ($v) => $v !== null && $v !== '');

            // Cada moneda seleccionada para el cobro de entrada debe existir y estar activa
            if ($feeByCurrency->isNotEmpty()) {
                $validIds = \App\Models\Currency::query()
                    ->where('status', 'ACTIVE')
                    ->whereIn('id', $feeByCurrency->keys()->all())
                    ->pluck('id')
                    ->all();

                foreach ($feeByCurrency->keys() as $currencyId) {
                    if (!in_array($currencyId, $validIds, true)) {
                        $validator->errors()->add(
                            'fee_by_currency',
                            'Una de las monedas de entrada seleccionadas no está disponible.'
                        );
                        break;
                    }
                }
            }

            // Si se define un premio fijo con monto, exigir moneda de premio
            $prizeType = $this->input('prize_type', 'accumulated');
            $prizeAmount = (float) $this->input('prize_amount', 0);

            if ($prizeType === 'fixed' && $prizeAmount > 0 && !$this->filled('prize_currency_id')) {
                $validator->errors()->add(
                    'prize_currency_id',
                    'Selecciona la moneda del premio cuando defines un premio fijo.'
                );
            }

            if ($prizeAmount > 0 && !$this->filled('prize_currency_id')) {
                $validator->errors()->add(
                    'prize_currency_id',
                    'Selecciona la moneda del premio cuando indicas un monto.'
                );
            }
        });
    }
}
