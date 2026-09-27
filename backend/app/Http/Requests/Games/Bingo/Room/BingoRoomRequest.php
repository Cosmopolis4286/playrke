<?php

namespace App\Http\Requests\Games\Bingo\Room;

use App\Http\Requests\BaseRequest;
use App\Models\ExchangeRate;
use App\Models\Game;
use App\Domains\Games\Bingo\ValueObjects\BingoRoomCapacityRules;
use App\Models\Games\Common\GameMode\GameMode;
use App\Models\Games\Common\PrizePool\PrizePoolType;
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

            // Capacidad (ver BingoRoomCapacityRules). Los mínimos son obligatorios:
            // el sorteo no inicia sin alcanzarlos y, si al vencer no se alcanzaron,
            // la sala se cancela y se reembolsa. Los máximos son opcionales (sin tope).
            'minCardsToStart'             => ['required', 'integer', 'min:1', 'max:' . BingoRoomCapacityRules::MAX_CARDS],
            'maxCardsToStart'             => ['nullable', 'integer', 'min:1', 'max:' . BingoRoomCapacityRules::MAX_CARDS],
            'maxPlayers'                  => ['nullable', 'integer', 'min:1', 'max:' . BingoRoomCapacityRules::MAX_PLAYERS],
            'minPlayers'                  => ['required', 'integer', 'min:1', 'max:' . BingoRoomCapacityRules::MAX_PLAYERS],
            'roomType'                    => ['required', 'exists:room_types,id'],
            'connectionType'              => ['required', 'exists:connection_types,id'],
            'gameMode'                    => ['required', 'exists:game_modes,id'],
            'gameVariant'                 => ['nullable', 'string', 'in:BINGO_75,BINGO_90,BINGO_80,BINGO_30'],
            'prizePoolType'               => ['required', 'exists:prize_pool_types,id'],

            // Sala privada (solo aplica cuando roomType = tipo de sala PRIVATE)
            'accessCode'                  => [$this->requiredIfPrivateRoom(), 'nullable', 'string', 'min:4', 'max:20'],
            'roomPassword'                => [$this->requiredIfPrivateRoom(create: true), 'nullable', 'string', 'min:6', 'max:100'],
            'hostUserId'                  => [$this->requiredIfPrivateRoom(), 'nullable', 'exists:users,id'],

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
            // Jackpot: tipo y % van juntos — un % sin destino se descuenta del bote
            // sin ir a ningún pozo, y un tipo sin % nunca recibe aportes.
            'jackpotPct'                  => ['required_with:jackpotType', 'nullable', 'numeric', 'min:1', 'max:15'],
            'jackpotType'                 => ['required_with:jackpotPct', 'nullable', 'exists:jackpot_types,id'],

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
        // Cada chequeo cruzado vive en su propio método y corre SIEMPRE: antes
        // era un solo closure con `return` tempranos en el bloque de monedas,
        // que salteaban en silencio todo lo que venía después (suma de %,
        // modo de juego, jackpot activo, capacidad…) ante un error de monedas.
        $validator->after(function ($validator) {
            $this->validatePrizePoolAmount($validator);
            $this->validateScheduleLeadTime($validator);
            $allowedCurrencies = $this->validateCurrencies($validator);
            $this->validateFixedRoundPrizes($validator, $allowedCurrencies);
            $this->validatePercentageSum($validator);
            $this->validateGameMode($validator);
            $this->validateMoveFreeSpace($validator);
            $this->validateCapacityPairs($validator);
            $this->validateJackpotActive($validator);
        });
    }

    /** Valor Fijo/Extra: obligatorio en todo bote no acumulativo (en el acumulativo ya se descartó en prepareForValidation()). */
    private function validatePrizePoolAmount($validator): void
    {
        $prizePoolType = $this->resolvedPrizePoolType();
        if (
            $prizePoolType && !$prizePoolType->isAccumulative()
            && $this->input('amountAddedPrizePool') === null
            && !$validator->errors()->has('amountAddedPrizePool')
        ) {
            $validator->errors()->add('amountAddedPrizePool', "El valor fijo/extra es obligatorio para \"{$prizePoolType->label}\".");
        }
    }

    /**
     * Fecha+hora al menos 10 minutos desde ahora. Solo aplica a creación; en
     * edición el usuario no puede cambiar una fecha pasada pero sí debe poder
     * guardar otros cambios sin que la fecha existente lo bloquee.
     */
    private function validateScheduleLeadTime($validator): void
    {
        $isCreate = $this->route('room') === null;
        $date = $this->input('date');
        $time = $this->input('time');
        if (!$isCreate || $this->input('startMode') !== 'SCHEDULED' || !$date || !$time) return;
        if ($validator->errors()->has('date') || $validator->errors()->has('time')) return;

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

    /**
     * Monedas permitidas: tasa activa (con 2+ monedas) y precio por moneda
     * (sin base USD). Devuelve las monedas resueltas — las del request o, si
     * no vinieron, las ya guardadas en la sala — para los chequeos que dependen
     * de ellas. Los `return` de acá solo cortan ESTE bloque.
     *
     * @return array<int, string|int>
     */
    private function validateCurrencies($validator): array
    {
        $allowedCurrencies = $this->input('allowedCurrencies');
        $cardPriceByCurrency = $this->input('cardPriceByCurrency');

        // Si no mandaron allowedCurrencies, lo sacamos del room actual
        if ($allowedCurrencies === null) {
            // `config` es HasOne: ->config ya es el modelo. (Antes era
            // ->config->first(), que ejecuta BingoRoomConfig::first() y
            // devolvía la primera config de TODA la tabla, no la de esta sala.)
            $allowedCurrencies = $this->route('room')?->config?->allowed_currencies ?? [];
        }
        if (!is_array($allowedCurrencies)) {
            $allowedCurrencies = [];
        }

        // Si no mandaron ninguno de los dos, no hay nada nuevo que validar acá
        if ($this->input('allowedCurrencies') === null && $cardPriceByCurrency === null) {
            return $allowedCurrencies;
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
                return $allowedCurrencies;
            }
        }

        // Si hay más de una moneda Y no usa base USD, cardPriceByCurrency es obligatorio
        $useBaseUsd = (bool) $this->input('useBaseUsd', true);
        if ($useBaseUsd || count($allowedCurrencies) <= 1) {
            return $allowedCurrencies;
        }

        if (!is_array($cardPriceByCurrency) || empty($cardPriceByCurrency)) {
            $validator->errors()->add(
                'cardPriceByCurrency',
                'Debe indicar el precio del cartón por cada moneda permitida.'
            );
            return $allowedCurrencies;
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

        return $allowedCurrencies;
    }

    /** prizeValueByCurrency obligatorio (> 0) por cada moneda en rondas con tipo 'fixed'. */
    private function validateFixedRoundPrizes($validator, array $allowedCurrencies): void
    {
        $roundsConfig = $this->input('roundsConfig', []);
        if (!is_array($roundsConfig)) return;

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

    /** %Sala + %Jackpot + %Seriales + %Rondas no puede superar 100. */
    private function validatePercentageSum($validator): void
    {
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
    }

    /**
     * gameMode: el exists:game_modes,id no verifica que el modo pertenezca al
     * juego Bingo — GameMode es un catálogo compartido entre juegos (Trivia, etc.)
     * y aceptar un id de otro juego dejaría la sala con un modo incoherente.
     */
    private function validateGameMode($validator): void
    {
        $gameMode = $this->input('gameMode');
        if ($gameMode === null || $validator->errors()->has('gameMode')) return;

        $bingoGameId = Game::where('name', 'BINGO')->value('id');
        $mode = GameMode::find($gameMode);

        if (!$mode || (string) $mode->game_id !== (string) $bingoGameId) {
            $validator->errors()->add('gameMode', 'El modo de juego seleccionado no es válido para Bingo.');
        }
    }

    /**
     * Mover casilla libre: solo tiene sentido junto con cartón personalizado
     * y solo en BINGO_75 (única variante con casilla libre).
     */
    private function validateMoveFreeSpace($validator): void
    {
        if ($this->input('moveFreeSpaceMode') === null) return;

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

    /**
     * Coherencia entre los 4 límites de capacidad (ver BingoRoomCapacityRules):
     * piso de jugadores según el modo de juego, mín. cartones ≥ mín. jugadores,
     * máximos ≥ mínimos y máx. cartones ≥ máx. jugadores. Un campo que no vino
     * en el request se toma del ya guardado en la sala.
     */
    private function validateCapacityPairs($validator): void
    {
        $currentConfig = $this->route('room')?->config; // HasOne (ver validateCurrencies)

        $value = function (string $key, string $column) use ($currentConfig): ?int {
            $raw = $this->has($key) ? $this->input($key) : ($currentConfig?->{$column} ?? null);
            return is_numeric($raw) ? (int) $raw : null;
        };

        $gameModeId = $this->input('gameMode', $currentConfig?->game_mode_id);
        $gameModeCode = $gameModeId ? GameMode::whereKey($gameModeId)->value('code') : null;

        $errors = BingoRoomCapacityRules::crossErrors(
            $value('minCardsToStart', 'min_cards_to_start'),
            $value('maxCardsToStart', 'max_cards_to_start'),
            $value('minPlayers', 'min_players'),
            $value('maxPlayers', 'max_players'),
            $gameModeCode,
        );

        foreach ($errors as $field => $message) {
            if (!$validator->errors()->has($field)) {
                $validator->errors()->add($field, $message);
            }
        }
    }

    /**
     * Un jackpot desactivado no debe poder recibir nuevos aportes — el jackpot
     * puede desactivarse mientras salas existentes lo siguen referenciando
     * (FK nullOnDelete, no restrictOnDelete).
     */
    private function validateJackpotActive($validator): void
    {
        $jackpotTypeId = $this->input('jackpotType');
        if ($jackpotTypeId === null || $validator->errors()->has('jackpotType')) return;

        $jackpotType = \App\Models\Games\Common\Jackpot\JackpotType::find($jackpotTypeId);
        if (!$jackpotType || !$jackpotType->isActive()) {
            $validator->errors()->add('jackpotType', 'El jackpot seleccionado no está activo.');
        }
    }

    /**
     * Con bote acumulativo el "Valor Fijo/Extra" no aplica (el campo se oculta
     * en el form) — se descarta para no guardar un monto residual de cuando
     * el usuario tenía otro tipo de bote seleccionado.
     */
    protected function prepareForValidation(): void
    {
        if ($this->resolvedPrizePoolType()?->isAccumulative()) {
            $this->merge(['amountAddedPrizePool' => null]);
        }
    }

    private ?PrizePoolType $prizePoolTypeCache = null;

    private function resolvedPrizePoolType(): ?PrizePoolType
    {
        $id = $this->input('prizePoolType');
        if ($id === null || $id === '') return null;
        if ($this->prizePoolTypeCache?->id !== (string) $id) {
            $this->prizePoolTypeCache = PrizePoolType::find($id);
        }
        return $this->prizePoolTypeCache;
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

            'minCardsToStart.required'        => 'Indique el mínimo de cartones para iniciar el sorteo.',

            'minCardsToStart.integer'         => 'La cantidad mínima de cartones debe ser un número entero.',
            'minCardsToStart.min'             => 'La cantidad mínima de cartones debe ser al menos 1.',
            'minCardsToStart.max'             => 'La cantidad mínima de cartones no puede superar 10000.',

            'maxCardsToStart.integer'          => 'El máximo de cartones debe ser un número entero.',
            'maxCardsToStart.min'              => 'El máximo de cartones debe ser al menos 1.',
            'maxCardsToStart.max'              => 'El máximo de cartones no puede superar 10000.',

            'maxPlayers.integer'              => 'El cupo máximo de jugadores debe ser un número entero.',
            'maxPlayers.min'                  => 'El cupo máximo de jugadores debe ser al menos 1.',
            'maxPlayers.max'                  => 'El cupo máximo de jugadores no puede superar 100000.',

            'minPlayers.required'             => 'Indique el mínimo de jugadores para iniciar el sorteo.',

            'minPlayers.integer'              => 'El mínimo de jugadores debe ser un número entero.',
            'minPlayers.min'                  => 'El mínimo de jugadores debe ser al menos 1.',
            'minPlayers.max'                  => 'El mínimo de jugadores no puede superar 100000.',

            'roomType.required'               => 'El tipo de sala es obligatorio.',
            'roomType.exists'                 => 'El tipo de sala seleccionado no es válido.',

            'connectionType.required'         => 'El tipo de conexión es obligatorio.',
            'connectionType.exists'           => 'El tipo de conexión seleccionado no es válido.',

            'gameMode.required'               => 'El modo de juego es obligatorio.',
            'gameMode.exists'                 => 'El modo de juego seleccionado no es válido.',

            'prizePoolType.required'          => 'El tipo de bote es obligatorio.',
            'prizePoolType.exists'            => 'El tipo de bote seleccionado no es válido.',

            'jackpotType.exists'              => 'El jackpot seleccionado no es válido.',
            'jackpotType.required_with'       => 'Elija un tipo de jackpot para usar el % de jackpot.',
            'jackpotPct.required_with'        => 'Ingrese el % de jackpot o quite el tipo de jackpot.',
            'jackpotPct.min'                  => 'El % de jackpot no puede ser menor a 1.',
            'jackpotPct.max'                  => 'El % de jackpot no puede ser mayor a 15.',
            'amountAddedPrizePool.min'        => 'El valor fijo/extra no puede ser menor a 20.',
            'amountAddedPrizePool.max'        => 'El valor fijo/extra no puede ser mayor a 10.000.',

            'accessCode.required_if'          => 'El código de acceso es obligatorio para salas privadas.',
            'accessCode.min'                  => 'El código de acceso debe tener al menos 4 caracteres.',
            'accessCode.max'                  => 'El código de acceso no puede superar 20 caracteres.',

            'roomPassword.required_if'        => 'La contraseña es obligatoria para salas privadas.',
            'roomPassword.min'                => 'La contraseña debe tener al menos 6 caracteres.',
            'roomPassword.max'                => 'La contraseña no puede superar 100 caracteres.',

            'hostUserId.required_if'          => 'El anfitrión es obligatorio para salas privadas.',
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
            'numSerials.max'                  => 'El número de seriales no puede ser mayor a 30.',

            'customCardMode.required_if'      => 'Debe seleccionar una modalidad de cartón personalizado.',
            'customCardMode.in'               => 'La modalidad de cartón personalizado no es válida.',
            'customCardPricePct.required_if'  => 'El % del precio del cartón personalizado es obligatorio.',
            'customCardEveryX.required_if'    => 'La cantidad de cartones para el cartón gratuito es obligatoria.',
            'customCardEveryX.min'            => 'Debe ser cada 2 cartones o más.',
            'customCardEveryX.max'            => 'No puede ser cada más de 10 cartones.',
        ];
    }
}
