<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomUpdateDTO;
use App\Exceptions\Domain\Games\Bingo\Room\BingoRoomException;
use App\Http\Resources\Games\Bingo\Room\BingoRoomResource;
use App\Models\Games\Bingo\Creator\BingoCreator;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Room\BingoRoomConfig;
use App\Models\Common\Utils\GameConfig;
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
            $rkeCommission = GameConfig::where('game_id', 'BINGO')
                ->where('param', 'COMMISSION')
                ->value('value') ?? 5;

            $prizePool = $dto->prizePoolType > 1 ? ($dto->amountAddedPrizePool ?? 0) : 0;

            do {
                $encryptedCode = Str::random(14);
            } while (BingoRoom::where('encrypted_code', $encryptedCode)->exists());

            $uuidCode = strtoupper(Str::uuid()->toString());

            $room = BingoRoom::create([
                'encrypted_code'  => $encryptedCode,
                'status_id'       => 2,
                'room_type'       => $dto->roomType,
                'creator_user_id' => $userId,
                'prizes_paid'     => false,
                'prize_pool'      => $prizePool,
                'uuid_code'       => $uuidCode,
            ]);

            $roomDate = $dto->date;
            $unixDate = Carbon::createFromFormat('Y-m-d H:i', "{$roomDate} {$dto->time}")->timestamp;

            BingoRoomConfig::create([
                'room_id'                 => $room->id,
                'creation'                => 'M',
                'creator_user_id'         => $userId,
                'game_mode_id'            => $dto->gameMode,
                'in_live'                 => false,
                'date'                    => $roomDate,
                'time'                    => $dto->time,
                'unix_date'               => $unixDate,
                'card_price'              => $dto->ctnPrice,
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

            BingoCreator::create([
                'room_id'            => $room->id,
                'user_id'            => $userId,
                'creator_commn'      => $dto->roomCommn,
                'card_price'         => $dto->ctnPrice,
                //'allowed_currencies' => $dto->allowedCurrencies,
                'status'             => 'ACTIVE',
            ]);

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
        $room->load([
            'config',
            'creator.user.timeZone',
            'creator.allowedCurrencies',
        ]);

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
    public function update(BingoRoom $room, BingoRoomUpdateDTO $dto): void
    {
        DB::transaction(function () use ($room, $dto) {

            $this->bingoRooms->update(
                $room->id,
                array_filter([
                    'name'       => $dto->name,
                    'phone'      => $dto->phone,
                    'commission' => $dto->commission,
                    'country_id' => $dto->country,
                ])
            );
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
        if ($room->status === 'DELETED') {
            throw BingoRoomException::invalidStatusChange();
        }

        // Usa changeStatus con userRelation para sincronizar BingoRoom y User
        $newStatus = $room->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        return $this->bingoRooms->changeStatus($room, $newStatus, 'user');
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
        if ($room->status === 'DELETED') {
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
