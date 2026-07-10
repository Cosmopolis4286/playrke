<?php

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\DTOs\Room\BingoRoomCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomUpdateDTO;
use App\Domains\Games\Bingo\Events\BingoLobbyRoomChanged;
use App\Domains\Games\Bingo\Events\BingoRoomUpdated;
use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Http\Resources\Games\Bingo\Room\BingoRoomResource;
use App\Domains\Games\Bingo\Models\Creator\BingoCreator;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\Models\Round\BingoRoundPrize;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Utils\GameConfig;
use App\Models\Games\Common\Utils\GameStatus;
use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
use App\Domains\Games\Bingo\Jobs\StartBingoDrawJob;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Services\ExchangeRate\ExchangeRateDomainService;
use App\Services\Utils\Snowflake\SnowflakeBatchService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        private readonly \App\Domains\Games\Bingo\Contracts\Room\BingoRoomRepositoryInterface $bingoRooms,
        private readonly \App\Domains\Games\Bingo\Contracts\Card\CardPrice\BingoCardPriceRepositoryInterface $bingoCardPrices,
        private readonly ExchangeRateDomainService $exchangeRateService,
        private readonly WalletService $walletService,
        private readonly BingoRoomAuditLogger $auditLogger,
        private readonly SnowflakeBatchService $snowflakeService,
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
        $room->load([
            'config', 'status', 'cardPrices',
            'rounds.prizes', 'rounds.prizes.prize',
            'cardPrizes.creatorUser', 'cardPrizes.winningCard', 'cardPrizes.round',
        ]);

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
        try {
            $payload = $this->saveRoomTransact($roomOrDto, $updateDto);
        } catch (BingoRoomException $e) {
            throw $e; // Excepciones de dominio se propagan sin envolver
        } catch (\Throwable $e) {
            // Preservar la excepción original como $previous para no perder el stack trace
            throw new BingoRoomException('Error al guardar la sala: ' . $e->getMessage(), 0, $e);
        }

        // Broadcasts FUERA de la transacción: el commit ya ocurrió
        ['room' => $room, 'statusCode' => $statusCode, 'wasActive' => $wasActive, 'freshRoom' => $freshRoom] = $payload;

        if ($freshRoom && $statusCode === 'ACTIVE') {
            broadcast(new BingoRoomUpdated(
                roomId:       (string) $room->uuid_code,
                prizePool:    (float)  $room->prize_pool,
                totalPlayers: (int)    $room->total_players,
                cardPriceUsd: (float) ($freshRoom->config?->card_price_usd ?? 0),
            ));
        }

        $this->broadcastLobbyStatusChange($room, $statusCode === 'ACTIVE', $wasActive);

        return true;
    }

    /**
     * Ejecuta la transacción de saveRoom y devuelve el payload de broadcast.
     * Separado de saveRoom() para que saveRoomOrSplit() pueda controlar
     * cuándo se emiten los eventos (después del outer commit).
     */
    private function saveRoomTransact(BingoRoom|BingoRoomCreateDTO $roomOrDto, ?BingoRoomUpdateDTO $updateDto = null): array
    {
        return DB::transaction(function () use ($roomOrDto, $updateDto) {

            $userId = Auth::id();
            $isCreate = $roomOrDto instanceof BingoRoomCreateDTO;
            $dto = $isCreate ? $roomOrDto : $updateDto;

                // Una sola query cacheada para todos los status IDs necesarios
                $statusMap    = GameStatus::codesMap(['ACTIVE', 'INACTIVE', 'IN_PROGRESS', 'FINISHED', 'CANCELLED']);
                $statusCode   = $dto->activateRoom ? 'ACTIVE' : 'INACTIVE';
                $statusId     = $statusMap[$statusCode] ?? null;
                $activeStatusId = $statusMap['ACTIVE'] ?? null;

                // Sala base (crear o usar existente)
                $room = $isCreate ? $this->createBaseRoom($userId, $dto, $statusId) : $roomOrDto;

                // Resolver configuración y prize pool
                ['rkeCommission' => $rkeCommission, 'prizePool' => $prizePool] = $this->resolveGameConfig($dto->prizePoolType, $dto->amountAddedPrizePool);

                $previousStartMode = $room->loadMissing('config')->config?->start_mode ?? 'SCHEDULED';
                $startMode = $dto->startMode ?? $previousStartMode;
                $unixDate = null;
                if ($startMode === 'SCHEDULED' && $dto->date && $dto->time) {
                    $normalizedTime = substr($dto->time, 0, 5);
                    $dateTime = "{$dto->date} {$normalizedTime}";
                    $creatorTz = $this->resolveCreatorTimezone($userId);
                    $unixDate = Carbon::createFromFormat('Y-m-d H:i', $dateTime, $creatorTz)->timestamp;
                }

                $wasActive = !$isCreate && (string) $room->status_id === (string) $activeStatusId;

                // No sobreescribir prize_pool si el juego está en estado terminal
                $lockedIds    = array_values(array_filter([
                    $statusMap['IN_PROGRESS'] ?? null,
                    $statusMap['FINISHED']    ?? null,
                    $statusMap['CANCELLED']   ?? null,
                ]));
                $dataToUpdate = (!$isCreate && in_array($room->status_id, $lockedIds))
                    ? []
                    : ['prize_pool' => $prizePool];

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
                        BingoRoomAuditLogger::EVENT_CREATED_UNSCHEDULED,
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
                        BingoRoomAuditLogger::EVENT_START_MODE_CHANGED,
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

                // Solo sincronizar precios si la clave vino en el request
                if ($dto->allowedCurrencies !== null) {
                    $this->syncCardPrices(
                        $room->id,
                        $dto->allowedCurrencies,
                        $dto->cardPriceUsd ?? 0,
                        $dto->cardPriceByCurrency ?? [],
                        $userId,
                    );
                }

                // Sincronizar rondas (necesita el config ID para room_config_id)
                $this->syncRounds($room->id, $config->id, $dto->roundsConfig ?? []);

                return [
                    'room'       => $room,
                    'statusCode' => $statusCode,
                    'wasActive'  => $wasActive,
                    'freshRoom'  => $room->fresh(['config']),
                ];
        });
    }

    /**
     * Guarda una sala, o si `roomsPerCurrency` está activo y hay 2+ monedas permitidas,
     * crea una sala independiente por cada moneda (cada una con `allowed_currencies = [moneda]`
     * y sus precios/premios filtrados a esa moneda).
     *
     * @param BingoRoomCreateDTO $dto
     * @return bool
     */
    public function saveRoomOrSplit(BingoRoomCreateDTO $dto): bool
    {
        if (!$dto->roomsPerCurrency || count($dto->allowedCurrencies) <= 1) {
            return $this->saveRoom($dto);
        }

        // Outer transaction agrupa todas las salas por moneda.
        // saveRoom() emite broadcasts normalmente cuando se llama sola,
        // pero aquí suprimimos sus broadcasts individuales y los colectamos
        // para emitirlos TODOS después del commit outer — evita broadcasts
        // de salas que aún no existen si una iteración posterior falla y revierte.
        $broadcastQueue = DB::transaction(function () use ($dto) {
            $queue = [];
            foreach ($dto->allowedCurrencies as $currencyId) {
                $perCurrencyDto = $this->sliceDtoForCurrency($dto, $currencyId);
                $queue[] = $this->saveRoomReturningBroadcast($perCurrencyDto);
            }
            return $queue;
        });

        foreach (array_filter($broadcastQueue) as $b) {
            if ($b['statusCode'] === 'ACTIVE' && $b['freshRoom']) {
                broadcast(new BingoRoomUpdated(
                    roomId:       (string) $b['room']->uuid_code,
                    prizePool:    (float)  $b['room']->prize_pool,
                    totalPlayers: (int)    $b['room']->total_players,
                    cardPriceUsd: (float) ($b['freshRoom']->config?->card_price_usd ?? 0),
                ));
            }
            $this->broadcastLobbyStatusChange($b['room'], $b['statusCode'] === 'ACTIVE', $b['wasActive']);
        }

        return true;
    }

    /**
     * Variante interna de saveRoom() que devuelve el payload de broadcast
     * en lugar de emitirlo — permite que saveRoomOrSplit() controle cuándo
     * se emiten los eventos respecto al outer DB::transaction.
     */
    private function saveRoomReturningBroadcast(BingoRoomCreateDTO $dto): ?array
    {
        $payload = $this->saveRoomTransact($dto);

        return [
            'room'       => $payload['room'],
            'statusCode' => $payload['statusCode'],
            'wasActive'  => $payload['wasActive'],
            'freshRoom'  => $payload['freshRoom'],
        ];
    }

    /**
     * Construye un nuevo DTO a partir de `$dto`, limitando `allowedCurrencies`,
     * `cardPriceByCurrency` y `prizeValueByCurrency` (rondas FIXED) a una sola moneda.
     *
     * @param BingoRoomCreateDTO $dto
     * @param string $currencyId
     * @return BingoRoomCreateDTO
     */
    private function sliceDtoForCurrency(BingoRoomCreateDTO $dto, string $currencyId): BingoRoomCreateDTO
    {
        $cardPriceByCurrency = isset($dto->cardPriceByCurrency[$currencyId])
            ? [$currencyId => $dto->cardPriceByCurrency[$currencyId]]
            : [];

        $roundsConfig = array_map(function ($round) use ($currencyId) {
            if (($round['prizeType'] ?? null) === 'fixed' && isset($round['prizeValueByCurrency'][$currencyId])) {
                $round['prizeValueByCurrency'] = [$currencyId => $round['prizeValueByCurrency'][$currencyId]];
            }
            return $round;
        }, $dto->roundsConfig ?? []);

        return BingoRoomCreateDTO::fromArray([
            'date'                  => $dto->date,
            'time'                  => $dto->time,
            'startMode'             => $dto->startMode,
            'roomType'              => $dto->roomType,
            'gameMode'              => $dto->gameMode,
            'gameVariant'           => $dto->gameVariant,
            'prizePoolType'         => $dto->prizePoolType,
            'allowedCurrencies'     => [$currencyId],
            'amountAddedPrizePool'  => $dto->amountAddedPrizePool,
            'roomCommn'             => $dto->roomCommission,
            'jackpotPct'            => $dto->jackpotPct,
            'timeChip'              => $dto->timeChip,
            'rounds'                => $dto->rounds,
            'roundsPct'             => $dto->roundsPct,
            'useSerials'            => $dto->useSerials,
            'serialsPct'            => $dto->serialsPct,
            'numSerials'            => $dto->numSerials,
            'roundsConfig'          => $roundsConfig,
            'useBaseUsd'            => $dto->useBaseUsd,
            'cardPriceUsd'          => $dto->cardPriceUsd,
            'usdExchangeRoundStep'  => $dto->usdExchangeRoundStep,
            'cardPriceByCurrency'   => $cardPriceByCurrency,
            'activateRoom'          => $dto->activateRoom,
            'roomsPerCurrency'      => false,
            'isSplitRoom'           => true,
            'allowCustomCard'       => $dto->allowCustomCard,
            'allowSpecialCard'      => $dto->allowSpecialCard,
            'customCardPricePct'    => $dto->customCardPricePct,
            'maxSpecialCards'       => $dto->maxSpecialCards,
            'minCardsToStart'       => $dto->minCardsToStart,
        ]);
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
        $room = null;
        $maxAttempts = 5;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $uuidCode = strtoupper(Str::uuid()->toString());
                $room = BingoRoom::create([
                    'status_id'       => $statusId,
                    'creator_user_id' => $userId,
                    'paid_prizes'     => 'N',
                    'prize_pool'      => 0,
                    'uuid_code'       => $uuidCode,
                ]);
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                // SQLSTATE 23000 = unique violation (MySQL); 23505 = unique violation (PostgreSQL)
                $sqlState = (string) ($e->errorInfo[0] ?? '');
                $isUnique = str_starts_with($sqlState, '23');
                if (!$isUnique || $attempt === $maxAttempts - 1) {
                    throw $e;
                }
            }
        }

        if ($room === null) {
            throw BingoRoomException::creationFailed();
        }

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
    private function buildConfigData($dto, int $userId, float $rkeCommission, ?int $unixDate, int $roomId, bool $isCreate): array
    {
        $base = [
            'room_type_id'            => $dto->roomType,
            'game_mode_id'            => $dto->gameMode,
            'game_variant'            => $dto->gameVariant ?? 'BINGO_75',
            'date'                    => $dto->date,
            'time'                    => $dto->time,
            'unix_date'               => $unixDate,
            'allowed_currencies'      => $dto->allowedCurrencies,
            'rooms_per_currency_split' => $dto->isSplitRoom ?? false,
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
            'allow_custom_card'       => $dto->allowCustomCard ?? false,
            'custom_card_price_pct'   => $dto->customCardPricePct ?? null,
            'allow_special_card'      => $dto->allowSpecialCard ?? false,
            'max_special_cards'       => $dto->maxSpecialCards ?? null,
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

        // En update, omitir allowed_currencies si no vino en el request (null = no modificar)
        if ($dto->allowedCurrencies === null && !$isCreate) {
            unset($base['allowed_currencies']);
        }

        if ($isCreate) {
            $base['room_id']         = $roomId;
            $base['is_scheduled']    = 'N';
            $base['creator_user_id'] = $userId;
            $base['in_live']         = 'N';
            $base['from_house']      = $this->resolveFromHouse($userId);
        }

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

    /** Cache de rol admin por userId dentro de la request actual (evita doble query). */
    private array $adminRoleCache = [];

    /**
     * Determina si el usuario tiene rol admin o superadmin vigente.
     */
    private function isUserAdmin(int $userId): bool
    {
        return $this->resolveAdminRole($userId) !== null;
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

        return $this->resolveAdminRole($userId);
    }

    /**
     * Devuelve el prefijo del rol admin/superadmin activo del usuario, o null si no tiene.
     * Resultado cacheado en memoria para la duración de la request (evita doble query
     * cuando isUserAdmin() y resolveActorRole() se llaman en el mismo flujo).
     */
    private function resolveAdminRole(int $userId): ?string
    {
        if (array_key_exists($userId, $this->adminRoleCache)) {
            return $this->adminRoleCache[$userId];
        }

        // orderBy con CASE en lugar de FIELD() para compatibilidad con SQLite en tests.
        $role = \DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $userId)
            ->whereIn('roles.prefix', ['admin', 'superadmin'])
            ->where(function ($q) {
                $q->whereNull('role_user.expires_at')
                    ->orWhere('role_user.expires_at', '>', now());
            })
            ->orderByRaw("CASE roles.prefix WHEN 'superadmin' THEN 0 ELSE 1 END")
            ->value('roles.prefix');

        return $this->adminRoleCache[$userId] = $role;
    }

    /**
     * Registra un evento de dominio delegando al BingoRoomAuditLogger centralizado.
     */
    private function recordAudit(BingoRoom $room, string $event, ?string $description = null, array $metadata = []): void
    {
        $userId    = Auth::id();
        $actorRole = $userId ? $this->resolveActorRole((int) $userId, $room) : 'system';

        $this->auditLogger->log(
            $room,
            $event,
            $actorRole,
            $description ?? '',
            $metadata,
            $userId ? (string) $userId : null,
        );
    }

    /**
     * Emite el evento BingoLobbyRoomChanged (canal bingo.lobby) según la
     * transición de disponibilidad de la sala, para que la vista
     * /bingo/game-rooms la agregue, actualice o quite en tiempo real.
     *
     * @param BingoRoom $room     Sala (se recarga fresca con sus relaciones públicas)
     * @param bool      $isActive Si la sala queda ACTIVE tras el cambio
     * @param bool      $wasActive Si la sala estaba ACTIVE antes del cambio
     */
    private function broadcastLobbyStatusChange(BingoRoom $room, bool $isActive, bool $wasActive): void
    {
        $action = match (true) {
            $isActive && !$wasActive => BingoLobbyRoomChanged::ACTION_ACTIVATED,
            !$isActive && $wasActive => BingoLobbyRoomChanged::ACTION_DEACTIVATED,
            $isActive                => BingoLobbyRoomChanged::ACTION_UPDATED,
            default                  => null,
        };

        if ($action === null) {
            return;
        }

        $freshRoom = $room->fresh(['config.roomType', 'config.gameMode', 'status']);
        if (!$freshRoom) {
            return;
        }

        // Notifica a los clientes en /bingo/game-rooms (lobby global)
        broadcast(new BingoLobbyRoomChanged($freshRoom, $action));

        // Notifica también a los clientes en el lobby individual de la sala
        // (bingo.room.{id}) para que actualicen el status_code en tiempo real.
        broadcast(new BingoRoomUpdated(
            roomId:       (string) $freshRoom->id,
            prizePool:    (float)  $freshRoom->prize_pool,
            totalPlayers: (int)    $freshRoom->total_players,
            cardPriceUsd: (float) ($freshRoom->config?->card_price_usd ?? 0),
            totalCards:   (int)    $freshRoom->total_cards,
            statusCode:   $freshRoom->status?->code,
        ));
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
        $ids = $this->snowflakeService->batch(count($allowedCurrencies));

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
        if (empty($roundsConfig)) {
            // Sin config de rondas: eliminar las existentes y salir
            BingoRound::where('room_id', $roomId)->delete();
            return;
        }

        // Eliminar rondas previas antes de recrear (y sus premios en cascada)
        BingoRound::where('room_id', $roomId)->delete();

        $prizeTypeMap = [
            'percentage' => 'PCT',
            'fixed'      => 'FIXED',
            'prize'      => 'OTHER',
        ];

        $ids = $this->snowflakeService->batch(count($roundsConfig));

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
            $prizeIds     = $this->snowflakeService->batch($totalPrizes);
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
        // Validaciones y UPDATE dentro de una transacción con lockForUpdate para
        // eliminar la ventana TOCTOU entre la lectura del estado y la escritura.
        [$result, $wasActive, $newStatus] = DB::transaction(function () use ($room) {
            $locked = BingoRoom::where('id', $room->id)
                ->with(['status', 'config'])
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                return [false, false, null];
            }

            if ($locked->status?->code === 'DELETED') {
                throw BingoRoomException::invalidStatusChange();
            }

            if (
                $locked->config
                && $locked->config->unix_date !== null
                && $locked->config->unix_date <= Carbon::now('UTC')->timestamp
            ) {
                throw BingoRoomException::expiredRoomStatusLocked();
            }

            $wasActive = $locked->status?->code === 'ACTIVE';
            $newStatus = $wasActive ? 'INACTIVE' : 'ACTIVE';

            $room->setRelations($locked->getRelations());
            $room->fill($locked->getAttributes());

            return [$this->bingoRooms->changeGameStatus($room->id, $newStatus), $wasActive, $newStatus];
        });

        if ($result) {
            $this->broadcastLobbyStatusChange($room, $newStatus === 'ACTIVE', $wasActive);
            $this->auditLogger->log(
                $room,
                BingoRoomAuditLogger::EVENT_STATUS_CHANGED,
                'admin',
                "Estado cambiado de {$room->status?->code} a {$newStatus}",
                ['previous_status' => $room->status?->code, 'new_status' => $newStatus, 'changed_by' => Auth::id()],
            );
        }

        return $result;
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
        // Todas las validaciones y el UPDATE dentro de una transacción con lockForUpdate
        // para eliminar la ventana TOCTOU entre la lectura del estado y la escritura.
        $changed = DB::transaction(function () use ($room) {
            // Releer la sala con bloqueo exclusivo para evitar que dos peticiones
            // simultáneas de cancelación ambas pasen la guardia.
            $locked = BingoRoom::where('id', $room->id)
                ->with(['status', 'config'])
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                return false;
            }

            if ($locked->status?->code === 'DELETED') {
                throw BingoRoomException::invalidStatusChange();
            }

            if ($locked->status?->code !== 'ACTIVE') {
                throw new BingoRoomException('Solo se pueden cancelar salas con estado ACTIVE');
            }

            if (($locked->total_players ?? 0) === 0) {
                throw new BingoRoomException('Solo se pueden cancelar salas que tengan jugadores registrados');
            }

            // Propagate fresh data to $room so callers downstream see the latest state
            $room->setRelations($locked->getRelations());
            $room->fill($locked->getAttributes());

            return $this->bingoRooms->changeGameStatus($room->id, 'CANCELLED');
        });

        if (!$changed) {
            return false;
        }

        $this->broadcastLobbyStatusChange($room, false, true);

        $this->auditLogger->log(
            $room,
            BingoRoomAuditLogger::EVENT_CANCELLED,
            'admin',
            'Sala cancelada',
            ['cancelled_by' => Auth::id(), 'total_players' => $room->total_players ?? 0],
        );

        // Reembolsar cartones fuera de la transacción de estado.
        $this->auditLogger->log(
            $room,
            BingoRoomAuditLogger::EVENT_REFUND_INITIATED,
            'system',
            'Reembolso de cartones iniciado',
            ['total_players' => $room->total_players ?? 0],
        );

        $this->refundCardLotsForRoom($room);

        return true;
    }

    /**
     * Reembolsa los cartones comprados en una sala cancelada.
     * Itera todos los BingoCardLots activos, localiza la wallet del comprador
     * en la moneda correspondiente y acredita el sale_amount.
     */
    private function refundCardLotsForRoom(BingoRoom $room): void
    {
        // chunkById evita cargar todos los IDs en memoria de una vez (salas con miles de lotes)
        // y no mantiene el PDO abierto — seguro despachar jobs dentro del callback.
        BingoCardLot::where('room_id', $room->id)
            ->where('status', 'ACTIVE')
            ->chunkById(200, function ($lots) use ($room) {
                foreach ($lots as $lot) {
                    BingoCardLotRefundJob::dispatch((string) $lot->id, (string) $room->id)
                        ->onQueue('bingo-refunds');
                }
            });
    }

    private function createRefundFailure(BingoCardLot $lot, BingoRoom $room, float $amount, string $reason): void
    {
        BingoLotRefundFailure::create([
            'room_id'       => $room->id,
            'lot_id'        => $lot->id,
            'user_id'       => $lot->user_id,
            'currency_id'   => $lot->currency_id,
            'amount'        => $amount,
            'error_message' => $reason,
            'status'        => 'PENDING',
        ]);
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

            StartBingoDrawJob::dispatch((string) $room->id)->onQueue('bingo-draw');

            $this->recordAudit(
                $room,
                BingoRoomAuditLogger::EVENT_MANUALLY_ACTIVATED,
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
                BingoRoomAuditLogger::EVENT_START_MODE_CHANGED,
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

        // La copia se crea sin fecha/hora para evitar que quede con unix_date en el
        // pasado (si la sala original ya expiró) y sea inmediatamente expirada por el cron.
        // start_mode = MANUAL permite al administrador activarla cuando lo desee.
        $dto = BingoRoomCreateDTO::fromArray([
            'date'                  => null,
            'time'                  => null,
            'startMode'             => 'MANUAL',
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
        // Guards y softDelete dentro de transacción con lockForUpdate para evitar
        // la ventana TOCTOU entre la lectura de status y la escritura del DELETE.
        return DB::transaction(function () use ($room): bool {
            $locked = BingoRoom::where('id', $room->id)
                ->with('status')
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                throw BingoRoomException::notFound();
            }

            if ($locked->status?->code === 'DELETED') {
                throw BingoRoomException::alreadyDeleted();
            }

            if (($locked->total_players ?? 0) > 0) {
                throw BingoRoomException::roomHasPlayers();
            }

            $statusId = GameStatus::codeToId('DELETED');

            return $this->bingoRooms->softDelete($locked, status: $statusId);
        });
    }
}
