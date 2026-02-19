<?php

namespace App\Services\Games\Bingo\Pattern;

use App\DTOs\Games\Bingo\Pattern\BingoPatternCreateDTO;
use App\DTOs\Games\Bingo\Pattern\BingoPatternUpdateDTO;
use App\Exceptions\Domain\Games\Bingo\Pattern\BingoPatternException;
use App\Http\Resources\Games\Bingo\Pattern\BingoPatternResource;
use App\Models\Games\Bingo\Creator\BingoCreator;
use App\Models\Games\Bingo\Pattern\BingoPattern;
use App\Models\Games\Bingo\Pattern\BingoPatternConfig;
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
final class BingoPatternCrudService
{
    public function __construct(
        private readonly \App\Contracts\Games\Bingo\Pattern\BingoPatternRepositoryInterface $bingoRooms,
        private readonly \App\Contracts\Games\Bingo\Card\CardPrice\BingoCardPriceRepositoryInterface $bingoCardPrices,
        private readonly ExchangeRateDomainService $exchangeRateService,
    ) {}

    /**
     * Obtener sala de bingo para visualización.
     *
     * @param BingoPattern $room
     * @return BingoPatternResource
     *
     * @throws BingoPatternException
     */
    public function show(BingoPattern $room): BingoPatternResource
    {
        if (!$room->exists) {
            throw BingoPatternException::notFound();
        }

        $room->load(['config', 'status', 'cardPrices']);

        return new BingoPatternResource($room);
    }

    /**
     * Guardar sala de bingo (create o update) con su configuración y precios de cartones.
     *
     * @param BingoPattern|BingoPatternCreateDTO $roomOrDto
     * @param BingoPatternUpdateDTO|null $updateDto Solo requerido para update
     * @return bool
     * @throws BingoPatternException Si ocurre un error durante la operación
     */
    public function saveRoom(BingoPattern|BingoPatternCreateDTO $roomOrDto, ?BingoPatternUpdateDTO $updateDto = null): bool
    {
        return DB::transaction(function () use ($roomOrDto, $updateDto) {

            $userId = Auth::id();
            $isCreate = $roomOrDto instanceof BingoPatternCreateDTO;
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
            BingoPatternConfig::updateOrCreate(
                ['room_id' => $room->id],
                $this->buildConfigData($dto, $userId, $rkeCommission, $unixDate, $room->id, $isCreate)
            );

            // Actualizar comisión del creador si es update
            if (!$isCreate) {
                BingoCreator::where('room_id', $room->id)
                    ->where('user_id', $room->creator_user_id)
                    ->update(['creator_commn' => $dto->roomCommn]);
            }

            // Sincronizar precios de cartones
            $this->syncCardPrices(
                $room->id,
                $dto->allowedCurrencies,
                $dto->cardPrice ?? 0,
                $dto->cardPriceByCurrency ?? [],
                $userId,
            );

            return true;
        });
    }

    /**
     * Crear sala base para create
     * - Crea registro en BingoPattern con estado ACTIVE y prize_pool 0
     * - Crea registro en BingoCreator para asociar creador con sala
     * - Genera uuid_code único para la sala
     * - NO crea configuración ni precios de cartones, eso se maneja en saveRoom después de crear la sala base
     *
     * @param int $userId ID del usuario creador
     * @param BingoPatternCreateDTO $dto DTO con datos de creación
     *
     * @return BingoPattern La sala creada con ID y UUID generado
     * @throws BingoPatternException Si ocurre un error durante la creación de la sala base
     */
    private function createBaseRoom(int $userId, BingoPatternCreateDTO $dto): BingoPattern
    {
        do {
            $uuidCode = strtoupper(Str::uuid()->toString());
        } while (BingoPattern::where('uuid_code', $uuidCode)->exists());

        $statusId = GameStatus::where('code', 'ACTIVE')->value('id');

        $room = BingoPattern::create([
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
     * @throws BingoPatternException Si el tipo de prize pool no es válido
     */
    private function resolveGameConfig(string|int $prizePoolTypeId, ?float $amountAddedPrizePool): array
    {
        $config = GameConfig::whereHas('game', fn($q) => $q->where('name', 'BINGO'))->pluck('value', 'param');
        $rkeCommission = $config['COMMISSION'] ?? 5;

        $type = PrizePoolType::find($prizePoolTypeId);
        if (!$type) throw BingoPatternException::invalidPrizePoolType();

        $prizePool = !$type->is_accumulative ? ($amountAddedPrizePool ?? 0) : 0;
        return compact('rkeCommission', 'prizePool');
    }

    /**
     * Construye el array de datos para la configuración de la sala
     *
     * @param BingoPatternCreateDTO|BingoPatternUpdateDTO $dto DTO con los datos de entrada
     * @param int $userId ID del usuario para asignar como creador en caso de creación
     * @param float $rkeCommission Comisión de RKE resuelta desde configuración
     * @param int $unixDate Fecha y hora del juego en formato timestamp
     * @param int $roomId ID de la sala de bingo (necesario para creación)
     * @param bool $isCreate Indica si es operación de creación o actualización
     *
     * @return array Array de datos para crear o actualizar BingoPatternConfig
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
     * Alternar estado de una sala de bingo (ACTIVE / INACTIVE).
     * También sincroniza el estado del usuario asociado.
     *
     * @param BingoPattern $room
     * @return bool True si el cambio fue exitoso
     *
     * @throws BingoPatternException Si la sala de bingo está eliminada
     */
    public function toggleStatus(BingoPattern $room): bool
    {
        if ($room->status?->code === 'DELETED') {
            throw BingoPatternException::invalidStatusChange();
        }

        // Definir nuevo estado alternando entre ACTIVE y INACTIVE
        $newStatus = $room->status?->code === 'ACTIVE'
            ? 'INACTIVE'
            : 'ACTIVE';

        return $this->bingoRooms->changeGameStatus($room->id, $newStatus);
    }

    /**
     * Eliminación lógica (soft delete) de una sala de bingo.
     * También marca el usuario asociado como DELETED.
     *
     * @param BingoPattern $room
     * @return bool True si la eliminación fue exitosa
     *
     * @throws BingoPatternException Si la sala de bingo ya está eliminada
     */
    public function delete(BingoPattern $room): bool
    {
        if ($room->status?->code === 'DELETED') {
            throw BingoPatternException::alreadyDeleted();
        }

        $statusId = GameStatus::where('code', 'DELETED')->value('id');

        // Usa softDelete con userRelation para sincronizar BingoPattern
        return $this->bingoRooms->softDelete($room, status: $statusId);
    }
}
