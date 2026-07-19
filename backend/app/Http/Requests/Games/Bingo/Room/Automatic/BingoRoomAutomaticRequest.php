<?php

namespace App\Http\Requests\Games\Bingo\Room\Automatic;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para validar datos de una Sala de Bingo Automatica/Autogenerable (BingoRoomAutomatic).
 *
 * - Se usa para creación y actualización de salas automaticas.
 * - Aplica reglas dinámicas según el contexto del formulario.
 */
class BingoRoomAutomaticRequest extends BaseRequest
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
            'activateRoom'                => ['boolean'],
            'days'                        => ['required', 'array', 'min:1'],
            'days.*'                      => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'date'                        => ['nullable', 'date', 'after_or_equal:today'],
            'time'                        => ['required', 'date_format:H:i'],
            'roomType'                    => ['required', 'exists:room_types,id'],
            'gameMode'                    => ['required', 'exists:game_modes,id'],
            'prizePoolType'               => ['required', 'exists:prize_pool_types,id'],

            // Precio base del cartón en USD:
            // - Si useBaseUsd = true → cardPriceUsd es obligatorio
            // - Si useBaseUsd = false → puede venir null o no enviarse
            // - Si viene con valor → siempre debe ser numérico y estar entre 0.5 y 500
            'cardPriceUsd' => ['required_if:useBaseUsd,true', 'nullable', 'numeric', 'min:0.5', 'max:500'],

            // Incremento de redondeo para conversión USD (null = sin redondeo)
            'usdExchangeRoundStep'         => ['nullable', 'numeric', 'min:0.01', 'max:100'],

            // Monedas permitidas
            'allowedCurrencies'           => ['required', 'array', 'min:1'],
            'allowedCurrencies.*'         => ['required', 'exists:currencies,id'],

            // Si hay 2+ monedas permitidas: generar una sala independiente por cada moneda
            'roomsPerCurrency'            => ['sometimes', 'boolean'],

            // Precios por moneda
            // NOTA: la validación fuerte se hace en withValidator()
            'cardPriceByCurrency'          => ['required_if:useBaseUsd,false', 'nullable', 'array'],
            'cardPriceByCurrency.*'        => ['nullable', 'numeric', 'min:1', 'max:5000'],

            // Pool de premios
            'amountAddedPrizePool'        => ['nullable', 'numeric', 'min:20', 'max:10000'],

            // Comisiones
            'roomCommn'                   => ['required', 'numeric', 'min:10', 'max:50'],
            'jackpotPct'                  => ['nullable', 'numeric', 'min:1', 'max:15'],

            // Configuración general del juego
            'timeChip'                    => ['required', 'integer', 'min:3', 'max:10'],
            'rounds'                      => ['required', 'integer', 'min:1', 'max:15'],
            'roundsPct'                   => ['nullable', 'numeric', 'min:5', 'max:90'],
            'useSerials'                  => ['boolean'],
            'serialsPct'                  => ['required_if:useSerials,true', 'nullable', 'numeric', 'min:2', 'max:35'],
            'numSerials'                  => ['required_if:useSerials,true', 'nullable', 'integer', 'min:1', 'max:30'],

            // Configuración de rondas
            'roundsConfig'                                  => ['nullable', 'array'],
            'roundsConfig.*.prizeType'                      => ['required_with:roundsConfig', 'in:percentage,fixed,prize'],
            'roundsConfig.*.bingoPattern'                   => ['nullable', 'exists:bingo_patterns,id'],
            'roundsConfig.*.prizeValue'                     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'roundsConfig.*.prizeValueByCurrency'           => ['nullable', 'array'],
            'roundsConfig.*.prizeValueByCurrency.*'         => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'roundsConfig.*.prizeId'                        => ['nullable', 'exists:prizes,id'],
            'roundsConfig.*.prizeName'                      => ['nullable', 'string', 'max:255'],

            // Nuevos campos de programación avanzada
            'recurrenceType'             => ['nullable', 'string', 'in:DAILY,WEEKLY,MONTHLY,CUSTOM'],
            'recurrenceDays'               => ['nullable', 'array'],
            'recurrenceDays.*'             => ['integer', 'min:0', 'max:6'], // 0=domingo, 6=sábado
            'gameTime'                     => ['nullable', 'date_format:H:i'],
            'startDate'                    => ['nullable', 'date', 'after_or_equal:today'],
            'endDate'                      => ['nullable', 'date', 'after_or_equal:startDate'],
            'defaultConfig'                => ['nullable', 'array'],
            'defaultCardPrice'             => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'defaultCurrencyId'            => ['nullable', 'exists:currencies,id'],
            'autoCreateRoom'             => ['boolean'],
            'maxRoomsPerDay'             => ['nullable', 'integer', 'min:1', 'max:100'],

            // Mínimo de cartones para iniciar el juego
            'minCardsToStart'            => ['nullable', 'integer', 'min:1', 'max:10000'],

            // Días de anticipación para publicar la sala antes del inicio del juego
            // null = mismo día; 1..30 = la sala se publica N días antes
            'salesStartOffsetDays'       => ['nullable', 'integer', 'min:1', 'max:30'],

            // Cartón personalizado
            'allowCustomCard'             => ['boolean'],
            'customCardMode'              => ['required_if:allowCustomCard,true', 'nullable', 'in:paid,every_x,free'],
            'customCardPricePct'          => ['required_if:customCardMode,paid', 'nullable', 'numeric', 'min:1', 'max:10'],
            'customCardEveryX'            => ['required_if:customCardMode,every_x', 'nullable', 'integer', 'min:2', 'max:10'],
            'allowSpecialCard'            => ['boolean'],
            'maxSpecialCards'             => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    /**
     * Validaciones adicionales después de las reglas básicas.
     *
     * - Si hay más de una o mas monedas permitidas, debe venir cardPriceByCurrency
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

            $useBaseUsd = (bool) $this->input('useBaseUsd', true);
            if ($useBaseUsd) {
                return;
            }

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

            // Validar prizeValueByCurrency para rondas con tipo 'fixed'
            $roundsConfig = $this->input('roundsConfig', []);
            if (is_array($roundsConfig)) {
                foreach ($roundsConfig as $i => $config) {
                    if (($config['prizeType'] ?? '') !== 'fixed') {
                        continue;
                    }
                    $byCurrency = $config['prizeValueByCurrency'] ?? [];
                    foreach ($allowedCurrencies as $currencyId) {
                        $key = (string) $currencyId;
                        if (
                            !array_key_exists($key, $byCurrency)
                            || $byCurrency[$key] === null
                            || $byCurrency[$key] === ''
                            || (float) $byCurrency[$key] <= 0
                        ) {
                            $validator->errors()->add(
                                "roundsConfig.$i.prizeValueByCurrency.$key",
                                "Falta el valor fijo de la ronda " . ($i + 1) . " para la moneda $key."
                            );
                        }
                    }
                }
            }

            // Validar que la suma de porcentajes no supere 100
            $useSerials  = filter_var($this->input('useSerials', false), FILTER_VALIDATE_BOOLEAN);
            $roomCommn   = (float) ($this->input('roomCommn')  ?? 0);
            $jackpotPct  = (float) ($this->input('jackpotPct') ?? 0);
            $serialsPct  = $useSerials ? (float) ($this->input('serialsPct') ?? 0) : 0;
            $roundsPct   = (float) ($this->input('roundsPct')  ?? 0);
            $totalPct    = $roomCommn + $jackpotPct + $serialsPct + $roundsPct;

            if ($totalPct > 100) {
                $validator->errors()->add(
                    'percentageSum',
                    "La suma de porcentajes ({$totalPct}%) no puede exceder el 100%."
                );
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
            'days.required'                   => 'Debe seleccionar al menos un día de publicación.',
            'days.array'                      => 'Los días deben ser un listado válido.',
            'days.min'                        => 'Debe seleccionar al menos un día de publicación.',
            'days.*.in'                       => 'Uno o más días seleccionados no son válidos.',

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

            'cardPriceUsd.required'            => 'El precio del cartón en USD es obligatorio.',
            'cardPriceUsd.min'                 => 'El precio del cartón no puede ser menor a 0.5.',
            'cardPriceUsd.max'                 => 'El precio del cartón no puede ser mayor a 500.',

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

            'serialsPct.required_if'          => 'El porcentaje de seriales es obligatorio cuando se usan seriales.',
            'serialsPct.min'                  => 'El porcentaje de seriales no puede ser menor a 2.',
            'serialsPct.max'                  => 'El porcentaje de seriales no puede ser mayor a 35.',

            'numSerials.required_if'          => 'El número de seriales es obligatorio cuando se usan seriales.',
            'numSerials.min'                  => 'El número de seriales debe ser al menos 1.',

            'minCardsToStart.integer'         => 'El mínimo de cartones debe ser un número entero.',
            'minCardsToStart.min'             => 'El mínimo de cartones debe ser al menos 1.',
            'minCardsToStart.max'             => 'El mínimo de cartones no puede superar 10.000.',

            'salesStartOffsetDays.integer'    => 'Los días de anticipación deben ser un número entero.',
            'salesStartOffsetDays.min'        => 'Los días de anticipación deben ser al menos 1.',
            'salesStartOffsetDays.max'        => 'Los días de anticipación no pueden superar 30.',

            'customCardMode.required_if'      => 'Debe seleccionar una modalidad de cartón personalizado.',
            'customCardMode.in'               => 'La modalidad de cartón personalizado no es válida.',
            'customCardPricePct.required_if'  => 'El % del precio del cartón personalizado es obligatorio.',
            'customCardEveryX.required_if'    => 'La cantidad de cartones para el cartón gratuito es obligatoria.',
            'customCardEveryX.min'            => 'Debe ser cada 2 cartones o más.',
            'customCardEveryX.max'            => 'No puede ser cada más de 10 cartones.',
        ];
    }
}
