<?php

namespace App\Http\Requests\Games\Bingo\Room;

use App\Http\Requests\BaseRequest;
use App\Models\ExchangeRate;
use App\Models\Game;
use App\Models\Games\Common\GameMode\GameMode;
use App\Models\Games\Common\Room\RoomType;
use App\Services\Auth\PermissionService;

/**
 * Request para validar datos de una Sala de Bingo (BingoRoom).
 *
 * - Se usa para creación y actualización de salas.
 * - Aplica reglas dinámicas según el contexto del formulario.
 */
class BingoRoomRequest extends BaseRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['superadmin', 'admin', 'commerce', 'customer']);
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
            'startMode'                   => ['required', 'in:SCHEDULED,MANUAL'],
            'date'                        => array_filter(['required_if:startMode,SCHEDULED', 'nullable', 'date', $this->route('room') ? null : 'after_or_equal:today']),
            'time'                        => ['required_if:startMode,SCHEDULED', 'nullable', 'date_format:H:i'],

            // Cantidad mínima de cartones vendidos para permitir el inicio del sorteo (opcional)
            'minCardsToStart'             => ['nullable', 'integer', 'min:1', 'max:10000'],
            // Cupo máximo de jugadores en la sala (opcional, sin límite si no se envía)
            'maxPlayers'                  => ['nullable', 'integer', 'min:1', 'max:100000'],
            'roomType'                    => ['required', 'exists:room_types,id'],
            'connectionType'              => ['required', 'exists:connection_types,id'],
            'gameMode'                    => ['nullable', 'exists:game_modes,id'],
            'gameVariant'                 => ['nullable', 'string', 'in:BINGO_75,BINGO_90,BINGO_80,BINGO_30'],
            'prizePoolType'               => ['required', 'exists:prize_pool_types,id'],

            // Sala privada (solo aplica cuando roomType = tipo de sala PRIVATE)
            'accessCode'                  => [$this->requiredIfPrivateRoom(), 'nullable', 'string', 'min:4', 'max:20'],
            'roomPassword'                => [$this->requiredIfPrivateRoom(create: true), 'nullable', 'string', 'min:6', 'max:100'],
            'hostUserId'                  => ['nullable', 'exists:users,id'],

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

            // Si hay 2+ monedas permitidas: crear una sala independiente por cada moneda
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

            // Cartón personalizado
            'allowCustomCard'             => ['boolean'],
            'customCardMode'              => ['required_if:allowCustomCard,true', 'nullable', 'in:paid,by_range,every_x,free'],
            'customCardPricePct'          => ['required_if:customCardMode,paid', 'nullable', 'numeric', 'min:1', 'max:5'],
            'customCardEveryX'            => ['required_if:customCardMode,every_x', 'nullable', 'integer', 'min:2', 'max:10'],
            'moveFreeSpaceMode'           => ['nullable', 'in:paid,by_range,free'],
            'moveFreeSpacePricePct'       => ['required_if:moveFreeSpaceMode,paid', 'nullable', 'numeric', 'min:1', 'max:5'],
            'allowSpecialCard'            => ['boolean'],
            'maxSpecialCards'             => ['nullable', 'integer', 'min:1', 'max:5'],

            // Configuración de rondas
            'roundsConfig'                                  => ['nullable', 'array'],
            'roundsConfig.*.prizeType'                      => ['required_with:roundsConfig', 'in:percentage,fixed,prize'],
            'roundsConfig.*.bingoPattern'                   => ['nullable', 'exists:bingo_patterns,id'],
            'roundsConfig.*.prizeValue'                     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'roundsConfig.*.prizeValueByCurrency'           => ['nullable', 'array'],
            // min:0.01 porque withValidator() exige > 0 para rondas de tipo 'fixed'
            'roundsConfig.*.prizeValueByCurrency.*'         => ['nullable', 'numeric', 'min:0.01', 'max:1000000'],
            'roundsConfig.*.prizeId'                        => ['nullable', 'exists:prizes,id'],
            'roundsConfig.*.prizeName'                      => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Regla `required_if:roomType,<id>` resuelta contra el id real del RoomType
     * con código PRIVATE (roomType viaja como snowflake id, no como código).
     *
     * @param bool $create Si true, y estamos en creación, la regla es más estricta
     *                      (usada por roomPassword: obligatoria solo al crear, opcional
     *                      al editar para no forzar cambiarla cada vez).
     */
    private function requiredIfPrivateRoom(bool $create = false): string
    {
        if ($create && $this->route('room') !== null) {
            return 'nullable';
        }

        $privateRoomTypeId = RoomType::where('code', 'PRIVATE')->value('id');

        return $privateRoomTypeId ? "required_if:roomType,{$privateRoomTypeId}" : 'nullable';
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

            // Validar que fecha+hora sea al menos 10 minutos desde ahora
            // Solo aplica a creación; en edición el usuario no puede cambiar una fecha pasada
            // pero sí debe poder guardar otros cambios sin que la fecha existente lo bloquee.
            $isCreate = $this->route('room') === null;
            $date = $this->input('date');
            $time = $this->input('time');
            if ($isCreate && $this->input('startMode') === 'SCHEDULED' && $date && $time && !$validator->errors()->has('date') && !$validator->errors()->has('time')) {
                $userTz     = auth()->user()?->timeZone?->name ?? 'UTC';
                $scheduled  = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", $userTz);
                $minAllowed = \Carbon\Carbon::now($userTz)->addMinutes(10);

                if ($scheduled->lt($minAllowed)) {
                    $validator->errors()->add(
                        'time',
                        'La hora debe ser al menos 10 minutos desde ahora (' . $minAllowed->format('H:i') . ' o posterior).'
                    );
                }
            }

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

            // Con 2+ monedas permitidas, todas necesitan tasa de cambio activa: la
            // compra de cartones siempre deriva el equivalente en USD a partir de la
            // tasa, sin importar si el precio se definió en USD base o manualmente
            // por moneda (useBaseUsd false). Defensa en profundidad: el frontend ya
            // bloquea/advierte esto, pero el backend es la fuente de verdad final.
            if (count($allowedCurrencies) > 1) {
                $currenciesWithActiveRate = ExchangeRate::active()
                    ->whereIn('currency_id', $allowedCurrencies)
                    ->pluck('currency_id')
                    ->map(fn ($id) => (string) $id)
                    ->unique()
                    ->all();

                $missingRate = array_filter(
                    $allowedCurrencies,
                    fn ($currencyId) => !in_array((string) $currencyId, $currenciesWithActiveRate, true)
                );

                if (!empty($missingRate)) {
                    $validator->errors()->add(
                        'allowedCurrencies',
                        'Las siguientes monedas no tienen tasa de cambio activa: ' . implode(', ', $missingRate) . '.'
                    );
                    return;
                }
            }

            // Si hay más de una moneda Y no usa base USD, cardPriceByCurrency es obligatorio
            $useBaseUsd = (bool) $this->input('useBaseUsd', true);
            if (!$useBaseUsd && count($allowedCurrencies) > 1) {

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

            // gameMode: el exists:game_modes,id no verifica que el modo pertenezca al
            // juego Bingo — GameMode es un catálogo compartido entre juegos (Trivia, etc.)
            // y aceptar un id de otro juego dejaría la sala con un modo incoherente.
            $gameMode = $this->input('gameMode');
            if ($gameMode !== null && !$validator->errors()->has('gameMode')) {
                $bingoGameId = Game::where('name', 'BINGO')->value('id');
                $mode = GameMode::find($gameMode);

                if (!$mode || (string) $mode->game_id !== (string) $bingoGameId) {
                    $validator->errors()->add('gameMode', 'El modo de juego seleccionado no es válido para Bingo.');
                }
            }

            // Mover casilla libre: solo tiene sentido junto con cartón personalizado
            // y solo en BINGO_75 (única variante con casilla libre).
            $moveFreeSpaceMode = $this->input('moveFreeSpaceMode');
            if ($moveFreeSpaceMode !== null) {
                if (!filter_var($this->input('allowCustomCard', false), FILTER_VALIDATE_BOOLEAN)) {
                    $validator->errors()->add(
                        'moveFreeSpaceMode',
                        'Mover la casilla libre requiere permitir cartón personalizado.'
                    );
                }

                $gameVariant = $this->input('gameVariant', \App\Domains\Games\Bingo\ValueObjects\BingoVariant::BINGO_75);
                if ($gameVariant !== \App\Domains\Games\Bingo\ValueObjects\BingoVariant::BINGO_75) {
                    $validator->errors()->add(
                        'moveFreeSpaceMode',
                        'Mover la casilla libre solo aplica a Bingo 75 bolas.'
                    );
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
            'startMode.required'              => 'El modo de inicio es obligatorio.',
            'startMode.in'                    => 'El modo de inicio debe ser "Programado" o "Manual".',

            'date.required_if'                => 'La fecha es obligatoria para salas con horario programado.',
            'date.after_or_equal'             => 'La fecha debe ser hoy o posterior.',

            'time.required_if'                => 'La hora es obligatoria para salas con horario programado.',
            'time.date_format'                => 'La hora debe tener formato HH:mm.',

            'minCardsToStart.integer'         => 'La cantidad mínima de cartones debe ser un número entero.',
            'minCardsToStart.min'             => 'La cantidad mínima de cartones debe ser al menos 1.',
            'minCardsToStart.max'             => 'La cantidad mínima de cartones no puede superar 10000.',

            'maxPlayers.integer'              => 'El cupo máximo de jugadores debe ser un número entero.',
            'maxPlayers.min'                  => 'El cupo máximo de jugadores debe ser al menos 1.',
            'maxPlayers.max'                  => 'El cupo máximo de jugadores no puede superar 100000.',

            'roomType.required'               => 'El tipo de sala es obligatorio.',
            'roomType.exists'                 => 'El tipo de sala seleccionado no es válido.',

            'connectionType.required'         => 'El tipo de conexión es obligatorio.',
            'connectionType.exists'           => 'El tipo de conexión seleccionado no es válido.',

            'gameMode.exists'                 => 'El modo de juego seleccionado no es válido.',

            'prizePoolType.required'          => 'El tipo de bote es obligatorio.',
            'prizePoolType.exists'            => 'El tipo de bote seleccionado no es válido.',

            'accessCode.required_if'          => 'El código de acceso es obligatorio para salas privadas.',
            'accessCode.min'                  => 'El código de acceso debe tener al menos 4 caracteres.',
            'accessCode.max'                  => 'El código de acceso no puede superar 20 caracteres.',

            'roomPassword.required_if'        => 'La contraseña es obligatoria para salas privadas.',
            'roomPassword.min'                => 'La contraseña debe tener al menos 6 caracteres.',
            'roomPassword.max'                => 'La contraseña no puede superar 100 caracteres.',

            'hostUserId.exists'               => 'El usuario anfitrión seleccionado no es válido.',

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

            'customCardMode.required_if'      => 'Debe seleccionar una modalidad de cartón personalizado.',
            'customCardMode.in'               => 'La modalidad de cartón personalizado no es válida.',
            'customCardPricePct.required_if'  => 'El % del precio del cartón personalizado es obligatorio.',
            'customCardEveryX.required_if'    => 'La cantidad de cartones para el cartón gratuito es obligatoria.',
            'customCardEveryX.min'            => 'Debe ser cada 2 cartones o más.',
            'customCardEveryX.max'            => 'No puede ser cada más de 10 cartones.',
        ];
    }
}
