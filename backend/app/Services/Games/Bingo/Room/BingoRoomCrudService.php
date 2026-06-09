<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomUpdateDTO;
use App\Exceptions\Domain\Games\Bingo\Room\BingoRoomException;
use App\Http\Resources\Games\Bingo\Room\BingoRoomResource;
use App\Domains\Games\Bingo\Models\Creator\BingoCreator;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAudit;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\Models\Round\BingoRoundPrize;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Utils\GameConfig;
use App\Models\Games\Common\Utils\GameStatus;
use App\Services\ExchangeRate\ExchangeRateDomainService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de aplicación para operaciones CRUD de salas de bingo.
 *
 * Responsabilidades:
 * - Resolver rol activo del usuario
 * - Resolver entidad asociada al rol
 * - Delegar operaciones a repositorios según rol
 *
 * NO maneja autenticación ni lógica HTTP.
 */
class BingoRoomCrudService
{
    public function __construct(
        private readonly \App\Contracts\Games\Bingo\Room\BingoRoomRepositoryInterface $bingoRooms,
        private readonly \App\Contracts\Games\Bingo\Card\CardPrice\BingoCardPriceRepositoryInterface $bingoCardPrices,
        private readonly ExchangeRateDomainService $exchangeRateService,
    ) {}

    /**
     * Obtener sala de bingo para visualización.
     *
     * @param BingoRoom $room
     * @return BingoRoomResource
     *
     * @throws BingoRoomException
     */
    public function show(BingoRoom $room): BingoRoomResource
    {
        if (!$room->exists) {
            throw BingoRoomException::notFound();
        }

        $room->refresh();
        $room->load(['config', 'status', 'cardPrices', 'rounds.prizes', 'rounds.prizes.prize']);

        return new BingoRoomResource($room);
    }

    /**
     * Guardar sala de bingo (create o update) con su configuración y precios de cartones.
     *
     * @param BingoRoom|BingoRoomCreateDTO $roomOrDto
     * @param BingoRoomUpdateDTO|null $updateDto Solo requerido para update
     * @return bool
     * @throws BingoRoomException Si ocurre un error durante la operación
     */
    public function saveRoom(BingoRoom|BingoRoomCreateDTO $roomOrDto, ?BingoRoomUpdateDTO $updateDto = null): bool
    {
        return DB::transaction(function () use ($roomOrDto, $updateDto) {

            $userId = Auth::id();
            $isCreate = $roomOrDto instanceof BingoRoomCreateDTO;
            $dto = $isCreate ? $roomOrDto : $updateDto;

            try {
                // Definir ID del estado de la sala
                $statusCode = $dto->activateRoom ? 'ACTIVE' : 'INACTIVE';
                $statusId = GameStatus::where('code', $statusCode)->value('id');

                // Sala base (crear o usar existente)
                $room = $isCreate ? $this->createBaseRoom($userId, $dto, $statusId) : $roomOrDto;

                // Resolver configuración y prize pool
                ['rkeCommission' => $rkeCommission, 'prizePool' => $prizePool] = $this->resolveGameConfig($dto->prizePoolType, $dto->amountAddedPrizePool);

                // Unix timestamp respetando la timezone del creador
                // Las salas de inicio manual (startMode = MANUAL) no tienen horario: unix_date queda null
                // hasta que el anfitrión/admin la active manualmente (ver activateNow()).
                $previousStartMode = $room->loadMissing('config')->config?->start_mode ?? 'SCHEDULED';
                $startMode = $dto->startMode ?? $previousStartMode;
                $unixDate = null;
                if ($startMode === 'SCHEDULED' && $dto->date && $dto->time) {
                    $dateTime = "{$dto->date} {$dto->time}";
                    $creatorTz = $this->resolveCreatorTimezone($userId);
                    $unixDate = Carbon::createFromFormat('Y-m-d H:i', $dateTime, $creatorTz)->timestamp;
                }

                // Datos a actualizar
                $dataToUpdate = ['prize_pool' => $prizePool];

                // Solo si hubo cambio del estado de la sala
                if ($room->status_id != $statusId) {
                    $dataToUpdate['status_id'] = $statusId;
                }

                // Actualizar sala
                $this->bingoRooms->update($room->id, $dataToUpdate);

                // Verificar que la sala se actualizó
                $room->refresh();

                $configData = $this->buildConfigData($dto, $userId, $rkeCommission, $unixDate, $room->id, $isCreate);

                // Configuración (create/update)
                $config = BingoRoomConfig::updateOrCreate(
                    ['room_id' => $room->id],
                    $configData
                );

                // Auditoría: registrar la creación de salas sin horario (requieren activación manual)
                if ($isCreate && ($configData['start_mode'] ?? 'SCHEDULED') === 'MANUAL') {
                    $this->recordAudit(
                        $room,
                        BingoRoomAudit::EVENT_CREATED_UNSCHEDULED,
                        'Sala creada sin horario de inicio — requiere activación manual del anfitrión o un administrador.',
                    );
                }

                // Auditoría: registrar el cambio de modo de inicio en una sala existente
                // (Programado <-> Manual). El anfitrión puede alternar el modo mientras
                // la sala no tenga jugadores registrados (la UI bloquea esa edición).
                if (!$isCreate && $startMode !== $previousStartMode) {
                    $description = $startMode === 'MANUAL'
                        ? 'La sala pasó de horario programado a inicio manual — ahora requiere activación manual del anfitrión o un administrador.'
                        : 'La sala pasó de inicio manual a horario programado — su sorteo se iniciará automáticamente según la fecha y hora configuradas.';

                    $this->recordAudit(
                        $room,
                        BingoRoomAudit::EVENT_START_MODE_CHANGED,
                        $description,
                        ['from' => $previousStartMode, 'to' => $startMode],
                    );
                }

                // Actualizar comisión del creador y estado si es update
                if (!$isCreate) {
                    BingoCreator::where('room_id', $room->id)
                        ->where('user_id', $room->creator_user_id)
                        ->update(['creator_commission' => $dto->roomCommission]);
                }

                // Sincronizar precios de cartones
                $this->syncCardPrices(
                    $room->id,
                    $dto->allowedCurrencies,
                    $dto->cardPriceUsd ?? 0,
                    $dto->cardPriceByCurrency ?? [],
                    $userId,
                );

                // Sincronizar rondas (necesita el config ID para room_config_id)
                $this->syncRounds($room->id, $config->id, $dto->roundsConfig ?? []);

                // Broadcast de actualización en tiempo real para clientes activos
                $freshRoom = $room->fresh(['config']);
                if ($freshRoom && $statusCode === 'ACTIVE') {
                    broadcast(new \App\Events\Games\Bingo\BingoRoomUpdated(
                        roomId:       (string) $room->uuid_code,
                        prizePool:    (float)  $room->prize_pool,
                        totalPlayers: (int)    $room->total_players,
                        cardPriceUsd: (float) ($freshRoom->config?->card_price_usd ?? 0),
                    ));
                }

                return true;
            } catch (\Exception $e) {
                throw new BingoRoomException('Error al guardar la sala: ' . $e->getMessage());
            }
        });
    }

    /**
     * Crear sala base para create
     * - Crea registro en BingoRoom con estado ACTIVE y prize_pool 0
     * - Crea registro en BingoCreator para asociar creador con sala
     * - Genera uuid_code único para la sala
     * - NO crea configuración ni precios de cartones, eso se maneja en saveRoom después de crear la sala base
     *
     * @param int $userId ID del usuario creador
     * @param BingoRoomCreateDTO $dto DTO con datos de creación
     * @param int $statusId Id del estado de la sala
     * @return BingoRoom La sala creada con ID y UUID generado
     * @throws BingoRoomException Si ocurre un error durante la creación de la sala base
     */
    protected function createBaseRoom(int $userId, BingoRoomCreateDTO $dto, int $statusId): BingoRoom
    {
        do {
            $uuidCode = strtoupper(Str::uuid()->toString());
        } while (BingoRoom::where('uuid_code', $uuidCode)->exists());

        $room = BingoRoom::create([
            'status_id'       => $statusId,
            'creator_user_id' => $userId,
            'paid_prizes'     => 'N',
            'prize_pool'      => 0,
            'uuid_code'       => $uuidCode,
        ]);

        BingoCreator::create([
            'room_id'       => $room->id,
            'user_id'       => $userId,
            'creator_commission' => $dto->roomCommission,
            'status'        => 'ACTIVE',
        ]);

        return $room;
    }

    /**
     * Resuelve configuración del juego y prize pool
     * - Obtiene comisión de RKE desde configuración general del juego
     * - Valida tipo de prize pool y calcula prize pool inicial según tipo (acumulativo o no acumulativo)
     *
     * @param string|int $prizePoolTypeId ID o valor del tipo de prize pool
     * @param float|null $amountAddedPrizePool Monto a agregar al prize pool (solo para no acumulativos)
     * @return array ['rkeCommission' => float, 'prizePool' => float]
     * @throws BingoRoomException Si el tipo de prize pool no es válido
     */
    private function resolveGameConfig(string|int $prizePoolTypeId, ?float $amountAddedPrizePool): array
    {
        $config = GameConfig::whereHas('game', fn($q) => $q->where('name', 'BINGO'))->pluck('value', 'param');
        $rkeCommission = $config['COMMISSION'] ?? 5;

        $type = PrizePoolType::find($prizePoolTypeId);
        if (!$type) throw BingoRoomException::invalidPrizePoolType();

        $prizePool = !$type->is_accumulative ? ($amountAddedPrizePool ?? 0) : 0;
        return compact('rkeCommission', 'prizePool');
    }

    /**
     * Construye el array de datos para la configuración de la sala
     *
     * @param BingoRoomCreateDTO|BingoRoomUpdateDTO $dto DTO con los datos de entrada
     * @param int $userId ID del usuario para asignar como creador en caso de creación
     * @param float $rkeCommission Comisión de RKE resuelta desde configuración
     * @param int $unixDate Fecha y hora del juego en formato timestamp
     * @param int $roomId ID de la sala de bingo (necesario para creación)
     * @param bool $isCreate Indica si es operación de creación o actualización
     *
     * @return array Array de datos para crear o actualizar BingoRoomConfig
     */
    private function buildConfigData($dto, int $userId, float $rkeCommission, int $unixDate, int $roomId, bool $isCreate): array
    {
        $base = [
            'room_type_id'            => $dto->roomType,
            'game_mode_id'            => $dto->gameMode,
            'game_variant'            => $dto->gameVariant ?? 'BINGO_75',
            'date'                    => $dto->date,
            'time'                    => $dto->time,
            'unix_date'               => $unixDate,
            'allowed_currencies'      => $dto->allowedCurrencies,
            'rke_commission'          => $rkeCommission,
            'room_commission'         => $dto->roomCommission,
            'use_base_usd'            => $dto->useBaseUsd ?? false,
            'card_price_usd'          => $dto->cardPriceUsd ?? null,
            'usd_exchange_round_step' => $dto->usdExchangeRoundStep ?? null,
            'prize_pool_type_id'      => $dto->prizePoolType,
            'amount_added_prize_pool' => $dto->amountAddedPrizePool ?? null,
            'time_chip'               => $dto->timeChip,
            'rounds'                  => $dto->rounds,
            'rounds_pct'              => $dto->roundsPct ?? null,
            'use_serials'             => $dto->useSerials ?? false,
            'serials_pct'             => $dto->serialsPct ?? null,
            'num_serials'             => $dto->numSerials ?? null,
            'jackpot_pct'             => $dto->jackpotPct ?? null,
        ];

        // start_mode: editable tanto en creación como en actualización — el anfitrión
        // puede alternar entre sala programada y sala manual mientras no tenga jugadores
        // (la UI bloquea la edición de salas con jugadores registrados). En creación el
        // DTO siempre trae un valor por defecto ('SCHEDULED'); en actualización puede
        // venir null si el formulario no lo envía, en cuyo caso se conserva el actual.
        if ($dto->startMode !== null) {
            $base['start_mode'] = $dto->startMode;
        }

        // min_cards_to_start: condición opcional para permitir el inicio del sorteo
        // (autónomo o manual). null = sin condición. Editable en creación y actualización.
        $base['min_cards_to_start'] = $dto->minCardsToStart;

        if ($isCreate) {
            $base['room_id']      = $roomId;
            $base['is_scheduled']     = 'N';
            $base['creator_user_id'] = $userId;
            $base['in_live']      = 'N';
            // from_house = Y si el creador es admin o superadmin; N para commerce/customer
            $base['from_house']   = $this->resolveFromHouse($userId);
        }
        // En update: from_house nunca se modifica — es inmutable tras la creación

        return $base;
    }

    /**
     * Determina si la sala es "propia de la app" según el rol del creador.
     * admin / superadmin → 'Y'
     * commerce / customer / otros → 'N'
     */
    private function resolveFromHouse(int $userId): string
    {
        return $this->isUserAdmin($userId) ? 'Y' : 'N';
    }

    /**
     * Determina si el usuario tiene rol admin o superadmin vigente.
     */
    private function isUserAdmin(int $userId): bool
    {
        $adminRoles = ['admin', 'superadmin'];

        return \DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $userId)
            ->whereIn('roles.prefix', $adminRoles)
            ->where(function ($q) {
                $q->whereNull('role_user.expires_at')
                    ->orWhere('role_user.expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Resuelve el rol bajo el cual el usuario actúa sobre la sala,
     * para fines de auditoría: 'creator' | 'admin' | 'superadmin' | null
     */
    private function resolveActorRole(int $userId, BingoRoom $room): ?string
    {
        if ((string) $room->creator_user_id === (string) $userId) {
            return 'creator';
        }

        $role = \DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $userId)
            ->whereIn('roles.prefix', ['admin', 'superadmin'])
            ->where(function ($q) {
                $q->whereNull('role_user.expires_at')
                    ->orWhere('role_user.expires_at', '>', now());
            })
            ->orderByRaw("FIELD(roles.prefix, 'superadmin', 'admin')")
            ->value('roles.prefix');

        return $role;
    }

    /**
     * Registra un evento de dominio en la bitácora dedicada de la sala (bingo_room_audits).
     */
    private function recordAudit(BingoRoom $room, string $event, ?string $description = null, array $metadata = []): void
    {
        $userId = Auth::id();

        BingoRoomAudit::create([
            'room_id'     => $room->id,
            'user_id'     => $userId,
            'event'       => $event,
            'actor_role'  => $userId ? $this->resolveActorRole((int) $userId, $room) : 'system',
            'description' => $description,
            'metadata'    => $metadata,
            'ip_address'  => request()?->ip(),
            'user_agent'  => request()?->userAgent(),
        ]);
    }

    /**
     * Sincroniza los precios de cartones por monedas usando upsert
     * - Inserta o actualiza precios según monedas permitidas
     * - Elimina precios de monedas que ya no están permitidas
     * - Si no hay monedas permitidas, elimina todos los precios asociados a la sala
     * - Si el precio de una moneda es 0 o negativo, obtiene el precio desde el servicio de tipo de cambio
     *
     * @param int $roomId ID de la sala de bingo
     * @param array $allowedCurrencies Array de IDs de monedas permitidas
     * @param array $cardPriceByCurrency Array asociativo [currency_id => card_price]
     * @param int $userId ID del usuario para obtener tipo de cambio si el precio es 0 o negativo
     * @param float $defaultPrice Precio por defecto si no se proporciona un precio específico para una moneda
     * @return void
     */
    protected function syncCardPrices(string $roomId, array $allowedCurrencies, float $defaultCardPrice, ?array $cardPriceByCurrency, string $creatorUserId): void
    {
        if (empty($allowedCurrencies)) {
            // Si no hay monedas permitidas, eliminar todos los precios de la sala
            $this->bingoCardPrices->query()
                ->where('room_id', $roomId)
                ->delete();
            return;
        }

        // Generar IDs Snowflake en lote
        $snowflakeService = app(\App\Services\Utils\Snowflake\SnowflakeBatchService::class);
        $ids = $snowflakeService->batch(count($allowedCurrencies));

        $pricesData = [];
        foreach ($allowedCurrencies as $index => $currencyId) {
            $cardPrice = $cardPriceByCurrency[$currencyId] ?? $defaultCardPrice ?? 0;

            // Si el precio es <= 0, obtener precio por exchange rate
            if ($cardPrice <= 0) {
                $cardPrice = $this->exchangeRateService->getExchangeRateByCurrency($currencyId, $creatorUserId);
            }

            $pricesData[] = [
                'id' => $ids[$index],               // ID único generado
                'room_id' => $roomId,
                'creator_user_id' => $creatorUserId,
                'currency_id' => $currencyId,
                'card_price' => $cardPrice,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Upsert masivo: crea o actualiza según room_id + currency_id
        $this->bingoCardPrices->query()->upsert(
            $pricesData,
            ['room_id', 'currency_id'],          // unique keys
            ['card_price', 'updated_at']         // campos a actualizar si ya existe
        );

        // Eliminar monedas que ya no están permitidas
        $this->bingoCardPrices->query()
            ->where('room_id', $roomId)
            ->whereNotIn('currency_id', $allowedCurrencies)
            ->delete();
    }


    /**
     * Resuelve la timezone del creador a partir de su time_zone_id.
     * Si no tiene timezone asignada, retorna UTC como fallback.
     *
     * @param int $userId ID del usuario creador
     * @return string Nombre de la timezone (ej: "America/Caracas")
     */
    private function resolveCreatorTimezone(int $userId): string
    {
        $tzName = \App\Models\User::where('users.id', $userId)
            ->join('time_zones', 'users.time_zone_id', '=', 'time_zones.id')
            ->value('time_zones.name');

        return $tzName ?? 'UTC';
    }

    /**
     * Sincroniza las rondas de una sala en bingo_rounds.
     * Elimina las rondas previas y recrea según el roundsConfig del DTO.
     *
     * Mapeo prize_type (frontend → DB):
     *   percentage → PCT
     *   fixed      → FIXED
     *   prize      → OTHER
     *
     * @param string $roomId
     * @param string $roomConfigId
     * @param array  $roundsConfig  [{prizeType, bingoPattern, prizeValue}]
     */
    protected function syncRounds(string $roomId, string $roomConfigId, array $roundsConfig): void
    {
        // Eliminar rondas previas (y sus premios en cascada)
        BingoRound::where('room_id', $roomId)->delete();

        if (empty($roundsConfig)) {
            return;
        }

        $prizeTypeMap = [
            'percentage' => 'PCT',
            'fixed'      => 'FIXED',
            'prize'      => 'OTHER',
        ];

        $snowflakeService = app(\App\Services\Utils\Snowflake\SnowflakeBatchService::class);
        $ids = $snowflakeService->batch(count($roundsConfig));

        $rows      = [];
        $prizeRows = []; // [round_id => [ [...entry], ... ]]

        foreach ($roundsConfig as $index => $config) {
            $prizeType = $prizeTypeMap[$config['prizeType'] ?? 'percentage'] ?? 'PCT';
            $roundId   = $ids[$index];

            $rows[] = [
                'id'               => $roundId,
                'room_id'          => $roomId,
                'room_config_id'   => $roomConfigId,
                'round'            => $index + 1,
                'bingo_pattern_id' => $config['bingoPattern'] ?? null,
                'prize_type'       => $prizeType,
                'status'           => 'ACTIVE',
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            // PCT — porcentaje sin moneda
            if ($prizeType === 'PCT' && isset($config['prizeValue'])) {
                $prizeRows[$roundId][] = [
                    'currency_id'       => null,
                    'prize_id'          => null,
                    'prize_value'       => (float) $config['prizeValue'],
                    'prize_description' => '',
                ];
            }

            // FIXED — un registro por moneda
            if ($prizeType === 'FIXED' && !empty($config['prizeValueByCurrency'])) {
                foreach ($config['prizeValueByCurrency'] as $currencyId => $value) {
                    if ($value === null || $value === '' || (float) $value <= 0) {
                        continue;
                    }
                    $prizeRows[$roundId][] = [
                        'currency_id'       => $currencyId,
                        'prize_id'          => null,
                        'prize_value'       => (float) $value,
                        'prize_description' => '',
                    ];
                }
            }

            // OTHER — premio físico, sin moneda ni valor numérico
            if ($prizeType === 'OTHER' && !empty($config['prizeId'])) {
                $prizeRows[$roundId][] = [
                    'currency_id'       => null,
                    'prize_id'          => $config['prizeId'],
                    'prize_value'       => null,
                    'prize_description' => $config['prizeName'] ?? '',
                ];
            }
        }

        BingoRound::insert($rows);

        // Insertar todos los premios en bingo_round_prizes
        if (!empty($prizeRows)) {
            $totalPrizes  = array_sum(array_map('count', $prizeRows));
            $prizeIds     = $snowflakeService->batch($totalPrizes);
            $prizeIdIndex = 0;
            $insertRows   = [];

            foreach ($prizeRows as $roundId => $entries) {
                foreach ($entries as $entry) {
                    $insertRows[] = array_merge([
                        'id'             => $prizeIds[$prizeIdIndex++],
                        'bingo_round_id' => $roundId,
                        'room_id'        => $roomId,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ], $entry);
                }
            }

            BingoRoundPrize::insert($insertRows);
        }
    }

    /**
     * Alternar estado de una sala de bingo (ACTIVE / INACTIVE).
     * También sincroniza el estado del usuario asociado.
     *
     * @param BingoRoom $room
     * @return bool True si el cambio fue exitoso
     *
     * @throws BingoRoomException Si la sala de bingo está eliminada
     */
    public function toggleStatus(BingoRoom $room): bool
    {
        if ($room->status?->code === 'DELETED') {
            throw BingoRoomException::invalidStatusChange();
        }

        // Bloquear si la fecha+hora de la sala ya expiró (comparar en UTC)
        // Las salas sin horario (unix_date null) no expiran por tiempo — se activan manualmente.
        $room->loadMissing('config');
        if ($room->config && $room->config->unix_date !== null && $room->config->unix_date <= Carbon::now('UTC')->timestamp) {
            throw BingoRoomException::expiredRoomStatusLocked();
        }

        // Definir nuevo estado alternando entre ACTIVE y INACTIVE
        $newStatus = $room->status?->code === 'ACTIVE'
            ? 'INACTIVE'
            : 'ACTIVE';

        return $this->bingoRooms->changeGameStatus($room->id, $newStatus);
    }

    /**
     * Cancelar una sala de bingo (cambiar estado a CANCELLED).
     * Solo se puede cancelar si la sala está ACTIVE y tiene jugadores.
     *
     * @param BingoRoom $room
     * @return bool True si la cancelación fue exitosa
     *
     * @throws BingoRoomException Si la sala no cumple las condiciones para cancelación
     */
    public function cancel(BingoRoom $room): bool
    {
        // Validar que la sala no esté eliminada
        if ($room->status?->code === 'DELETED') {
            throw BingoRoomException::invalidStatusChange();
        }

        // Validar que la sala esté ACTIVE
        if ($room->status?->code !== 'ACTIVE') {
            throw new BingoRoomException('Solo se pueden cancelar salas con estado ACTIVE');
        }

        // Validar que la sala tenga jugadores
        if (($room->total_players ?? 0) === 0) {
            throw new BingoRoomException('Solo se pueden cancelar salas que tengan jugadores registrados');
        }

        // Bloquear si la fecha+hora de la sala ya expiró (comparar en UTC)
        // Las salas sin horario (unix_date null) no expiran por tiempo — se activan manualmente.
        $room->loadMissing('config');
        if ($room->config && $room->config->unix_date !== null && $room->config->unix_date <= Carbon::now('UTC')->timestamp) {
            throw BingoRoomException::expiredRoomStatusLocked();
        }

        // Cambiar estado a CANCELLED
        return $this->bingoRooms->changeGameStatus($room->id, 'CANCELLED');
    }

    /**
     * Activa manualmente el sorteo de una sala de bingo sin horario programado
     * (start_mode = MANUAL). Solo puede ejecutarla el creador de la sala o un
     * usuario con rol admin/superadmin. Registra el evento en bingo_room_audits.
     *
     * @param BingoRoom $room
     * @return bool True si la activación fue despachada exitosamente
     *
     * @throws BingoRoomException Si la sala no admite activación manual, el usuario
     *                            no está autorizado, o la sala no está en un estado válido
     */
    public function activateNow(BingoRoom $room): bool
    {
        return DB::transaction(function () use ($room) {
            $room->loadMissing(['config', 'status', 'players']);

            // Solo aplica a salas configuradas como inicio manual
            if (($room->config?->start_mode ?? 'SCHEDULED') !== 'MANUAL') {
                throw BingoRoomException::activationNotAllowed();
            }

            $userId = Auth::id();
            $isCreator = $userId !== null && (string) $room->creator_user_id === (string) $userId;
            $isAdmin = $userId !== null && $this->isUserAdmin((int) $userId);

            if (!$isCreator && !$isAdmin) {
                throw BingoRoomException::activationForbidden();
            }

            // Solo se activa una sala disponible (ACTIVE) cuyo sorteo no haya iniciado
            if ($room->status?->code !== 'ACTIVE') {
                throw BingoRoomException::activationInvalidState();
            }

            $minCards = $room->config?->min_cards_to_start;
            if ($minCards !== null && ($room->total_cards ?? 0) < $minCards) {
                throw BingoRoomException::minCardsNotReached($minCards, $room->total_cards ?? 0);
            }

            $now = Carbon::now('UTC');

            $room->config?->update([
                'date'      => $now->toDateString(),
                'time'      => $now->format('H:i'),
                'unix_date' => $now->timestamp,
            ]);

            \App\Jobs\Games\Bingo\StartBingoDrawJob::dispatch((string) $room->id)->onQueue('bingo-draw');

            $this->recordAudit(
                $room,
                BingoRoomAudit::EVENT_MANUALLY_ACTIVATED,
                'Sala activada manualmente por ' . ($isCreator ? 'el anfitrión' : 'un administrador') . '.',
                [
                    'activated_at'   => $now->toIso8601String(),
                    'players_count'  => $room->total_players ?? 0,
                    'activated_role' => $isCreator ? 'creator' : 'admin',
                ],
            );

            return true;
        });
    }

    /**
     * Programa la fecha/hora de inicio de una sala sin horario (start_mode = MANUAL),
     * convirtiéndola en una sala programada (start_mode = SCHEDULED) que el comando
     * autónomo (bingo:start-pending-draws) recogerá cuando llegue su hora.
     *
     * Reutiliza la misma autorización y validaciones de estado que activateNow():
     * solo el creador de la sala o un admin/superadmin pueden programarla, y debe
     * estar ACTIVE sin sorteo iniciado.
     *
     * @param BingoRoom $room
     * @param string $date Fecha en formato Y-m-d
     * @param string $time Hora en formato H:i
     * @return bool True si la sala quedó programada exitosamente
     *
     * @throws BingoRoomException Si la sala no admite programación, el usuario no
     *                            está autorizado, el estado no es válido, o la
     *                            fecha/hora indicada no cumple el mínimo de 10 minutos
     */
    public function scheduleStart(BingoRoom $room, string $date, string $time): bool
    {
        return DB::transaction(function () use ($room, $date, $time) {
            $room->loadMissing(['config', 'status', 'players']);

            // Solo aplica a salas configuradas como inicio manual
            if (($room->config?->start_mode ?? 'SCHEDULED') !== 'MANUAL') {
                throw BingoRoomException::activationNotAllowed();
            }

            $userId = Auth::id();
            $isCreator = $userId !== null && (string) $room->creator_user_id === (string) $userId;
            $isAdmin = $userId !== null && $this->isUserAdmin((int) $userId);

            if (!$isCreator && !$isAdmin) {
                throw BingoRoomException::activationForbidden();
            }

            // Solo se programa una sala disponible (ACTIVE) cuyo sorteo no haya iniciado
            if ($room->status?->code !== 'ACTIVE') {
                throw BingoRoomException::activationInvalidState();
            }

            $creatorTz = $this->resolveCreatorTimezone((int) $room->creator_user_id);
            $scheduled = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", $creatorTz);

            if ($scheduled->lt(Carbon::now($creatorTz)->addMinutes(10))) {
                throw BingoRoomException::scheduleStartInvalidDate();
            }

            $unixDate = $scheduled->timestamp;

            $room->config?->update([
                'date'       => $date,
                'time'       => $time,
                'unix_date'  => $unixDate,
                'start_mode' => 'SCHEDULED',
            ]);

            $this->recordAudit(
                $room,
                BingoRoomAudit::EVENT_START_MODE_CHANGED,
                'Inicio programado para el ' . $scheduled->format('d/m/Y H:i') . ' por ' . ($isCreator ? 'el anfitrión' : 'un administrador') . ' — la sala pasó de inicio manual a horario programado.',
                [
                    'from'           => 'MANUAL',
                    'to'             => 'SCHEDULED',
                    'scheduled_for'  => $scheduled->toIso8601String(),
                    'scheduled_role' => $isCreator ? 'creator' : 'admin',
                ],
            );

            return true;
        });
    }

    /**
     * Copia una sala de bingo existente (status INACTIVE).
     * Replica configuración, precios de cartones y rondas.
     *
     * @param BingoRoom $room Sala a copiar
     * @return bool True si la copia fue exitosa
     */
    public function copyRoom(BingoRoom $room): bool
    {
        $room->load(['config', 'cardPrices', 'rounds.prizes', 'rounds.prizes.prize']);

        $config = $room->config;

        if (!$config) {
            throw BingoRoomException::notFound();
        }

        $cardPriceByCurrency = $room->cardPrices
            ->pluck('card_price', 'currency_id')
            ->map(fn($price) => (float) $price)
            ->toArray();

        $roundsConfig = $room->rounds
            ->sortBy('round')
            ->values()
            ->map(function ($r) {
                $prizeType = match ($r->prize_type) {
                    'PCT'   => 'percentage',
                    'FIXED' => 'fixed',
                    default => 'prize',
                };

                $config = [
                    'prizeType'    => $prizeType,
                    'bingoPattern' => $r->bingo_pattern_id,
                    'prizeValue'   => $r->prize_value !== null ? (float) $r->prize_value : null,
                    'prizeId'      => $prizeType === 'prize' ? $r->prize_id : null,
                    'prizeName'    => $prizeType === 'prize' ? ($r->prize?->name ?? null) : null,
                ];

                if ($prizeType === 'fixed' && $r->prizes->isNotEmpty()) {
                    $config['prizeValueByCurrency'] = $r->prizes
                        ->pluck('prize_value', 'currency_id')
                        ->map(fn($v) => (float) $v)
                        ->toArray();
                }

                return $config;
            })
            ->toArray();

        $dto = BingoRoomCreateDTO::fromArray([
            'date'                  => Carbon::parse($config->getRawOriginal('date'))->format('Y-m-d'),
            'time'                  => $config->time,
            'roomType'              => $config->room_type_id,
            'gameMode'              => $config->game_mode_id,
            'gameVariant'           => $config->game_variant ?? 'BINGO_75',
            'prizePoolType'         => $config->prize_pool_type_id,
            'allowedCurrencies'     => $config->allowed_currencies ?? [],
            'amountAddedPrizePool'  => $config->amount_added_prize_pool ? (float) $config->amount_added_prize_pool : null,
            'roomCommn'             => (float) $config->room_commission,
            'useBaseUsd'            => (bool) $config->use_base_usd,
            'cardPriceUsd'          => $config->card_price_usd !== null ? (float) $config->card_price_usd : null,
            'usdExchangeRoundStep'  => $config->usd_exchange_round_step !== null ? (float) $config->usd_exchange_round_step : null,
            'cardPriceByCurrency'   => $cardPriceByCurrency,
            'jackpotPct'            => $config->jackpot_pct ? (float) $config->jackpot_pct : null,
            'timeChip'              => (int) $config->time_chip,
            'rounds'                => (int) $config->rounds,
            'roundsPct'             => $config->rounds_pct ? (float) $config->rounds_pct : null,
            'useSerials'            => (bool) $config->use_serials,
            'serialsPct'            => $config->serials_pct ? (float) $config->serials_pct : null,
            'numSerials'            => $config->num_serials ? (int) $config->num_serials : null,
            'roundsConfig'          => $roundsConfig,
            'activateRoom'          => false,
            'fromHouse'             => $config->from_house,
        ]);

        return $this->saveRoom($dto);
    }

    /**
     * Eliminación lógica (soft delete) de una sala de bingo.
     * También marca el usuario asociado como DELETED.
     *
     * @param BingoRoom $room
     * @return bool True si la eliminación fue exitosa
     *
     * @throws BingoRoomException Si la sala de bingo ya está eliminada
     */
    public function delete(BingoRoom $room): bool
    {
        if ($room->status?->code === 'DELETED') {
            throw BingoRoomException::alreadyDeleted();
        }

        if (($room->total_players ?? 0) > 0) {
            throw BingoRoomException::roomHasPlayers();
        }

        $statusId = GameStatus::where('code', 'DELETED')->value('id');

        // Usa softDelete con userRelation para sincronizar BingoRoom
        return $this->bingoRooms->softDelete($room, status: $statusId);
    }
}
