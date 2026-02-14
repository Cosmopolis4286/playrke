<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomUpdateDTO;
use App\Exceptions\Domain\Games\Bingo\Room\BingoRoomException;
use App\Http\Resources\Games\Bingo\Room\BingoRoomResource;
use App\Models\Game;
use App\Models\Games\Bingo\Creator\BingoCreator;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Room\BingoRoomConfig;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Utils\GameConfig;
use App\Models\Games\Common\Utils\GameStatus;
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
    ) {}

    /**
     * Crear una nueva sala de bingo con su configuración y creador.
     *
     * @param BingoRoomCreateDTO $dto
     * @return bool
     *
     * @throws BingoRoomException Si ocurre un error durante la creación
     */
    public function create(BingoRoomCreateDTO $dto): bool
    {
        return DB::transaction(function () use ($dto) {
            $userId = Auth::id();

            $gameId = Game::where('name', 'BINGO')->value('id');

            $rkeCommission = GameConfig::where('game_id', $gameId)
                ->where('param', 'COMMISSION')
                ->value('value') ?? 5;

            $prizePoolDescription = PrizePoolType::where('id', $dto->prizePoolType)->value('description');
            $isAccumulative = $prizePoolDescription === 'Acumulativo';
            $prizePool = !$isAccumulative ? ($dto->amountAddedPrizePool ?? 0) : 0;

            do {
                $uuidCode = strtoupper(Str::uuid()->toString());
            } while (BingoRoom::where('uuid_code', $uuidCode)->exists());


            $statusId = GameStatus::byValue('AVAILABLE')->value('id');

            // Crear sala de bingo
            $room = BingoRoom::create([
                'status_id'       => $statusId,
                'creator_user_id' => $userId,
                'paid_prizes'     => 'N',
                'prize_pool'      => $prizePool,
                'uuid_code'       => $uuidCode,
            ]);

            // Crear registro en BingoCreator para asociar el creador con la sala
            BingoCreator::create([
                'room_id'            => $room->id,
                'user_id'            => $userId,
                'creator_commn'      => $dto->roomCommn,
                'status'             => 'ACTIVE',
            ]);

            $roomDate = $dto->date;
            $unixDate = Carbon::createFromFormat('Y-m-d H:i', "{$roomDate} {$dto->time}")->timestamp;

            // Crear configuración de la sala de bingo
            BingoRoomConfig::create([
                'room_id'                 => $room->id,
                'creation'                => 'M',
                'room_type'               => $dto->roomType,
                'creator_user_id'         => $userId,
                'game_mode_id'            => $dto->gameMode,
                'in_live'                 => 'N',
                'date'                    => $roomDate,
                'time'                    => $dto->time,
                'unix_date'               => $unixDate,
                'allowed_currencies'      => $dto->allowedCurrencies ? json_encode($dto->allowedCurrencies) : null,
                'rke_commn'               => $rkeCommission,
                'room_commn'              => $dto->roomCommn,
                'prize_pool_type_id'      => $dto->prizePoolType,
                'amount_added_prize_pool' => $dto->amountAddedPrizePool ?? 0,
                'time_chip'               => $dto->timeChip,
                'rounds'                  => $dto->rounds,
                'rounds_pct'              => $dto->roundsPct ?? 0,
                'serials_pct'             => $dto->serialsPct,
                'num_serials'             => $dto->numSerials,
                'jackpot_pct'             => $dto->jackpotPct ?? 0,
            ]);

            // Crear precios de cartones para la sala (un registro por moneda permitida)
            foreach ($dto->allowedCurrencies as $currencyId) {
                $this->bingoCardPrices->create([
                    'room_id'         => $room->id,
                    'creator_user_id' => $userId,
                    'currency_id'     => $currencyId,
                    'card_price'      => $dto->ctnPrice,
                ]);
            }

            return true;
        });
    }

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

        $room->load(['config', 'status', 'bingoCreators']);

        return new BingoRoomResource($room);
    }

    /**
     * Actualizar sala de bingo y relaciones.
     *
     * @param BingoRoom $room
     * @param BingoRoomUpdateDTO $dto
     *
     * @throws BingoRoomException
     */
    /**
     * Actualizar sala de bingo y relaciones.
     *
     * @param BingoRoom $room
     * @param BingoRoomUpdateDTO $dto
     *
     * @throws BingoRoomException
     */
    public function update(BingoRoom $room, BingoRoomUpdateDTO $dto): void
    {
        DB::transaction(function () use ($room, $dto) {

            if (!$room->exists) {
                throw BingoRoomException::notFound();
            }

            $userId = Auth::id();

            $gameId = Game::where('name', 'BINGO')->value('id');

            $rkeCommission = GameConfig::where('game_id', $gameId)
                ->where('param', 'COMMISSION')
                ->value('value') ?? 5;

            // Validar prize pool type
            $prizePoolDescription = PrizePoolType::where('id', $dto->prizePoolType)->value('description');
            $isAccumulative = $prizePoolDescription === 'Acumulativo';

            $prizePool = !$isAccumulative ? ($dto->amountAddedPrizePool ?? 0) : 0;

            // Calcular unix_date
            $roomDate = $dto->date;
            $unixDate = Carbon::createFromFormat('Y-m-d H:i', "{$roomDate} {$dto->time}")->timestamp;

            // =============================
            // Actualizar BingoRoom
            // =============================
            $this->bingoRooms->update(
                $room->id,
                array_filter([
                    'prize_pool' => $prizePool,
                ], fn($value) => !is_null($value))
            );

            // =============================
            // Actualizar Config
            // =============================
            BingoRoomConfig::where('room_id', $room->id)->update([
                'room_type'               => $dto->roomType,
                'game_mode_id'            => $dto->gameMode,
                'date'                    => $roomDate,
                'time'                    => $dto->time,
                'unix_date'               => $unixDate,
                'allowed_currencies'      => $dto->allowedCurrencies ? json_encode($dto->allowedCurrencies) : null,
                'rke_commn'               => $rkeCommission,
                'room_commn'              => $dto->roomCommn,
                'prize_pool_type_id'      => $dto->prizePoolType,
                'amount_added_prize_pool' => $dto->amountAddedPrizePool ?? 0,
                'time_chip'               => $dto->timeChip,
                'rounds'                  => $dto->rounds,
                'rounds_pct'              => $dto->roundsPct ?? 0,
                'serials_pct'             => $dto->serialsPct,
                'num_serials'             => $dto->numSerials,
                'jackpot_pct'             => $dto->jackpotPct ?? 0,
            ]);

            // =============================
            // Actualizar Creator (comisión)
            // =============================
            BingoCreator::where('room_id', $room->id)
                ->where('user_id', $room->creator_user_id)
                ->update([
                    'creator_commn' => $dto->roomCommn,
                ]);

            // =============================
            // Sincronizar Card Prices
            // =============================
            $allowedCurrencies = $dto->allowedCurrencies ?? [];

            $existingCurrencyIds = $this->bingoCardPrices
                ->query()
                ->where('room_id', $room->id)
                ->pluck('currency_id')
                ->toArray();

            // Monedas a eliminar
            $currenciesToDelete = array_diff($existingCurrencyIds, $allowedCurrencies);

            if (!empty($currenciesToDelete)) {
                $this->bingoCardPrices
                    ->query()
                    ->where('room_id', $room->id)
                    ->whereIn('currency_id', $currenciesToDelete)
                    ->delete();
            }

            // Crear o actualizar monedas permitidas
            foreach ($allowedCurrencies as $currencyId) {

                $existing = $this->bingoCardPrices
                    ->query()
                    ->where('room_id', $room->id)
                    ->where('currency_id', $currencyId)
                    ->first();

                if ($existing) {
                    $this->bingoCardPrices->update($existing->id, [
                        'card_price' => $dto->ctnPrice,
                    ]);
                } else {
                    $this->bingoCardPrices->create([
                        'room_id'         => $room->id,
                        'creator_user_id' => $userId,
                        'currency_id'     => $currencyId,
                        'card_price'      => $dto->ctnPrice,
                    ]);
                }
            }
        });
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
        if ($room->status?->value === 'DELETED') {
            throw BingoRoomException::invalidStatusChange();
        }

        // Definir nuevo estado alternando entre AVAILABLE y UNAVAILABLE
        $newStatus = $room->status?->value === 'AVAILABLE'
            ? 'UNAVAILABLE'
            : 'AVAILABLE';

        return $this->bingoRooms->changeGameStatus($room->id, $newStatus);
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
        if ($room->status?->value === 'DELETED') {
            throw BingoRoomException::alreadyDeleted();
        }

        // Usa softDelete con userRelation para sincronizar BingoRoom
        return $this->bingoRooms->softDelete(
            model: $room,
            status: 'DELETED',
            relationsToDetach: [],
        );
    }
}
