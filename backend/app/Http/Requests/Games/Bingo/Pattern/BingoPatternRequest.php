<?php

namespace App\Http\Requests\Games\Bingo\Pattern;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para validar datos de una Sala de Bingo (BingoRoom).
 *
 * - Se usa para creación y actualización de salas.
 * - Aplica reglas dinámicas según el contexto del formulario.
 */
class BingoPatternRequest extends BaseRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['admin', 'commerce', 'customer']);
    }

    /**
     * Reglas de validación dinámicas.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date'                        => ['required', 'date', 'after_or_equal:today'],
            'time'                        => ['required', 'date_format:H:i'],
            'roomType'                    => ['required', 'exists:room_types,id'],
            'gameMode'                    => ['required', 'exists:game_modes,id'],
            'prizePoolType'               => ['required', 'exists:prize_pool_types,id'],

            // Precio base del cartón:
            // - Si solo hay 1 moneda, este es el precio real
            // - Si hay varias monedas, puede ser usado como fallback
            'cardPrice'                    => ['required', 'numeric', 'min:0.5', 'max:500'],

            // Monedas permitidas
            'allowedCurrencies'           => ['required', 'array', 'min:1'],
            'allowedCurrencies.*'         => ['required', 'exists:currencies,id'],

            // Precios por moneda (cuando hay más de una moneda permitida)
            // NOTA: la validación fuerte se hace en withValidator()
            'cardPriceByCurrency'          => ['nullable', 'array'],
            'cardPriceByCurrency.*'        => ['nullable', 'numeric', 'min:1', 'max:5000'],

            // Pool de premios
            'amountAddedPrizePool'        => ['nullable', 'numeric', 'min:20', 'max:10000'],

            // Comisiones
            'roomCommn'                   => ['required', 'numeric', 'min:10', 'max:50'],
            'jackpotPct'                  => ['nullable', 'numeric', 'min:1', 'max:15'],

            // Configuración general del juego
            'timeChip'                    => ['required', 'integer', 'min:3', 'max:10'],
            'rounds'                      => ['required', 'integer', 'min:1', 'max:15'],
            'roundsPct'                   => ['nullable', 'numeric', 'min:3', 'max:30'],
            'serialsPct'                  => ['required', 'numeric', 'min:2', 'max:35'],
            'numSerials'                  => ['required', 'integer', 'min:3', 'max:30'],

            // Configuración de rondas
            'roundsConfig'                => ['nullable', 'array'],
            'roundsConfig.*.prizeType'    => ['required_with:roundsConfig', 'in:percentage,fixed'],
            'roundsConfig.*.bingoPattern' => ['nullable', 'exists:bingo_patterns,id'],
            'roundsConfig.*.prizeValue'   => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Validaciones adicionales después de las reglas básicas.
     *
     * - Si hay más de una moneda permitida, debe venir cardPriceByCurrency
     * - cardPriceByCurrency debe contener un precio para cada moneda seleccionada
     * - Si no se envían allowedCurrencies, se asume que no cambiaron y se valida contra las monedas actuales de la sala (en caso de update)
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $allowedCurrencies = $this->input('allowedCurrencies');
            $cardPriceByCurrency = $this->input('cardPriceByCurrency');

            // Si no mandaron ninguno, no validamos nada
            if ($allowedCurrencies === null && $cardPriceByCurrency === null) {
                return;
            }

            // Si no mandaron allowedCurrencies, lo sacamos del room actual
            if ($allowedCurrencies === null) {
                $room = $this->route('room'); // depende del nombre en tu ruta

                $config = $room?->config?->first();

                $allowedCurrencies = $config?->allowed_currencies ?? [];
            }

            if (!is_array($allowedCurrencies)) {
                $allowedCurrencies = [];
            }

            // Si hay más de una moneda, cardPriceByCurrency es obligatorio
            if (count($allowedCurrencies) > 1) {

                if (!is_array($cardPriceByCurrency) || empty($cardPriceByCurrency)) {
                    $validator->errors()->add(
                        'cardPriceByCurrency',
                        'Debe indicar el precio del cartón por cada moneda permitida.'
                    );
                    return;
                }

                foreach ($allowedCurrencies as $currencyId) {
                    $key = (string) $currencyId;

                    if (
                        !array_key_exists($key, $cardPriceByCurrency)
                        || $cardPriceByCurrency[$key] === null
                        || $cardPriceByCurrency[$key] === ''
                    ) {
                        $validator->errors()->add(
                            "cardPriceByCurrency.$key",
                            "Falta el precio del cartón para la moneda $key."
                        );
                    }
                }
            }
        });
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required'                   => 'La fecha es obligatoria.',
            'date.after_or_equal'             => 'La fecha debe ser hoy o posterior.',

            'time.required'                   => 'La hora es obligatoria.',
            'time.date_format'                => 'La hora debe tener formato HH:mm.',

            'roomType.required'               => 'El tipo de sala es obligatorio.',
            'roomType.exists'                 => 'El tipo de sala seleccionado no es válido.',

            'gameMode.required'               => 'El modo de juego es obligatorio.',
            'gameMode.exists'                 => 'El modo de juego seleccionado no es válido.',

            'prizePoolType.required'          => 'El tipo de bote es obligatorio.',
            'prizePoolType.exists'            => 'El tipo de bote seleccionado no es válido.',

            'cardPrice.required'               => 'El precio del cartón es obligatorio.',
            'cardPrice.min'                    => 'El precio del cartón no puede ser menor a 0.35.',
            'cardPrice.max'                    => 'El precio del cartón no puede ser mayor a 50.',

            'allowedCurrencies.required'      => 'Debe seleccionar al menos una moneda.',
            'allowedCurrencies.min'           => 'Debe seleccionar al menos una moneda.',
            'allowedCurrencies.*.exists'      => 'La moneda seleccionada no es válida.',

            'cardPriceByCurrency.array'        => 'Los precios por moneda deben venir en formato válido.',
            'cardPriceByCurrency.*.numeric'    => 'El precio del cartón por moneda debe ser numérico.',
            'cardPriceByCurrency.*.min'        => 'El precio del cartón por moneda no puede ser menor a 1.',
            'cardPriceByCurrency.*.max'        => 'El precio del cartón por moneda no puede ser mayor a 5000.',

            'roomCommn.required'              => 'La comisión de sala es obligatoria.',
            'roomCommn.min'                   => 'La comisión de sala no puede ser menor a 10.',
            'roomCommn.max'                   => 'La comisión de sala no puede ser mayor a 50.',

            'timeChip.required'               => 'El tiempo de ficha es obligatorio.',

            'rounds.required'                 => 'El número de rondas es obligatorio.',

            'serialsPct.required'             => 'El porcentaje de seriales es obligatorio.',

            'numSerials.required'             => 'El número de seriales es obligatorio.',
        ];
    }
}
