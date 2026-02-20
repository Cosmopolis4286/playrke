<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomUpdateDTO;
use App\Exceptions\Domain\Games\Bingo\Room\BingoRoomException;
use App\Http\Resources\Games\Bingo\Room\BingoRoomResource;
use App\Models\Games\Bingo\Creator\BingoCreator;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Room\BingoRoomConfig;
use App\Models\Games\Bingo\Rounds\BingoRound;
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
 *
 * @final
 */
final class BingoRoomCrudService
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

        $room->load(['config', 'status', 'cardPrices', 'rounds']);

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

            // Sala base (crear o usar existente)
            $room = $isCreate ? $this->createBaseRoom($userId, $dto) : $roomOrDto;

            // Resolver configuración y prize pool
            ['rkeCommission' => $rkeCommission, 'prizePool' => $prizePool] = $this->resolveGameConfig($dto->prizePoolType, $dto->amountAddedPrizePool);

            // Unix timestamp
            $unixDate = Carbon::createFromFormat('Y-m-d H:i', "{$dto->date} {$dto->time}")->timestamp;

            // Actualizar sala
            $this->bingoRooms->update($room->id, ['prize_pool' => $prizePool]);

            // Configuración (create/update)
            BingoRoomConfig::updateOrCreate(
                ['room_id' => $room->id],
                $this->buildConfigData($dto, $userId, $rkeCommission, $unixDate, $room->id, $isCreate)
            );

            // Actualizar comisión del creador si es update
            if (!$isCreate) {
                BingoCreator::where('room_id', $room->id)
                    ->where('user_id', $room->creator_user_id)
                    ->update(['creator_commn' => $dto->roomCommission]);
            }

            // Sincronizar precios de cartones
            $this->syncCardPrices(
                $room->id,
                $dto->allowedCurrencies,
                $dto->cardPrice ?? 0,
                $dto->cardPriceByCurrency ?? [],
                $userId,
            );

            // Sincronizar rondas
            $this->syncRounds($room->id, $dto->roundsConfig ?? []);

            return true;
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
     *
     * @return BingoRoom La sala creada con ID y UUID generado
     * @throws BingoRoomException Si ocurre un error durante la creación de la sala base
     */
    private function createBaseRoom(int $userId, BingoRoomCreateDTO $dto): BingoRoom
    {
        do {
            $uuidCode = strtoupper(Str::uuid()->toString());
        } while (BingoRoom::where('uuid_code', $uuidCode)->exists());

        $statusCode = $dto->isDraft ? 'DRAFT' : 'ACTIVE';
        $statusId = GameStatus::where('code', $statusCode)->value('id');

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
            'creator_commn' => $dto->roomCommission,
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
            'date'                    => $dto->date,
            'time'                    => $dto->time,
            'unix_date'               => $unixDate,
            'allowed_currencies'      => $dto->allowedCurrencies,
            'rke_commission'          => $rkeCommission,
            'room_commission'         => $dto->roomCommission,
            'prize_pool_type_id'      => $dto->prizePoolType,
            'amount_added_prize_pool' => $dto->amountAddedPrizePool ?? null,
            'time_chip'               => $dto->timeChip,
            'rounds'                  => $dto->rounds,
            'rounds_pct'              => $dto->roundsPct ?? null,
            'serials_pct'             => $dto->serialsPct,
            'num_serials'             => $dto->numSerials,
            'jackpot_pct'             => $dto->jackpotPct ?? null,
        ];

        if ($isCreate) {
            $base['room_id'] = $roomId;
            $base['creation'] = 'M';
            $base['creator_user_id'] = $userId;
            $base['in_live'] = 'N';
        }

        return $base;
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
    private function syncCardPrices(string $roomId, array $allowedCurrencies, float $defaultCardPrice, ?array $cardPriceByCurrency, string $creatorUserId): void
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
     * Sincroniza las rondas de una sala en bingo_rounds.
     * Elimina las rondas previas y recrea según el roundsConfig del DTO.
     *
     * Mapeo prize_type (frontend → DB):
     *   percentage → PCT
     *   fixed      → USD
     *   prize      → OTHER
     *
     * @param string $roomId
     * @param array  $roundsConfig  [{prizeType, bingoPattern, prizeValue}]
     */
    private function syncRounds(string $roomId, array $roundsConfig): void
    {
        // Eliminar rondas previas (recrear siempre para mantener consistencia)
        BingoRound::where('room_id', $roomId)->delete();

        if (empty($roundsConfig)) {
            return;
        }

        $prizeTypeMap = [
            'percentage' => 'PCT',
            'fixed'      => 'USD',
            'prize'      => 'OTHER',
        ];

        $snowflakeService = app(\App\Services\Utils\Snowflake\SnowflakeBatchService::class);
        $ids = $snowflakeService->batch(count($roundsConfig));

        $rows = [];
        foreach ($roundsConfig as $index => $config) {
            $rows[] = [
                'id'               => $ids[$index],
                'room_id'          => $roomId,
                'round'            => $index + 1,
                'bingo_pattern_id' => $config['bingoPattern'] ?? null,
                'prize_type'       => $prizeTypeMap[$config['prizeType'] ?? 'percentage'] ?? 'PCT',
                'prize_value'      => isset($config['prizeValue']) ? (float) $config['prizeValue'] : null,
                'status'           => 'ACTIVE',
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        BingoRound::insert($rows);
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

        // Definir nuevo estado alternando entre ACTIVE y INACTIVE
        $newStatus = $room->status?->code === 'ACTIVE'
            ? 'INACTIVE'
            : 'ACTIVE';

        return $this->bingoRooms->changeGameStatus($room->id, $newStatus);
    }

    /**
     * Copia una sala de bingo existente como nuevo borrador (status DRAFT).
     * Replica configuración, precios de cartones y rondas.
     *
     * @param BingoRoom $room Sala a copiar
     * @return bool True si la copia fue exitosa
     */
    public function copyRoom(BingoRoom $room): bool
    {
        $room->load(['config', 'cardPrices', 'rounds']);

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
            ->map(fn($r) => [
                'prizeType'    => match ($r->prize_type) {
                    'PCT'   => 'percentage',
                    'USD'   => 'fixed',
                    default => 'prize',
                },
                'bingoPattern' => $r->bingo_pattern_id,
                'prizeValue'   => $r->prize_value !== null ? (float) $r->prize_value : null,
            ])
            ->toArray();

        $dto = BingoRoomCreateDTO::fromArray([
            'date'                  => Carbon::parse($config->getRawOriginal('date'))->format('Y-m-d'),
            'time'                  => $config->time,
            'roomType'              => $config->room_type_id,
            'gameMode'              => $config->game_mode_id,
            'prizePoolType'         => $config->prize_pool_type_id,
            'cardPrice'             => (float) $config->card_price,
            'allowedCurrencies'     => $config->allowed_currencies ?? [],
            'amountAddedPrizePool'  => $config->amount_added_prize_pool ? (float) $config->amount_added_prize_pool : null,
            'roomCommn'             => (float) $config->room_commission,
            'jackpotPct'            => $config->jackpot_pct ? (float) $config->jackpot_pct : null,
            'timeChip'              => (int) $config->time_chip,
            'rounds'                => (int) $config->rounds,
            'roundsPct'             => $config->rounds_pct ? (float) $config->rounds_pct : null,
            'serialsPct'            => (float) $config->serials_pct,
            'numSerials'            => (int) $config->num_serials,
            'roundsConfig'          => $roundsConfig,
            'cardPriceByCurrency'   => $cardPriceByCurrency,
            'isDraft'               => true,
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

        $statusId = GameStatus::where('code', 'DELETED')->value('id');

        // Usa softDelete con userRelation para sincronizar BingoRoom
        return $this->bingoRooms->softDelete($room, status: $statusId);
    }
}
