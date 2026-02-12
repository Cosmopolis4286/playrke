<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\Models\Games\Bingo\Creator\BingoCreator;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Room\BingoRoomConfig;
use App\Models\Shared\Utils\GameConfig;
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
    public function __construct() {}

    /**
     * Crear una nueva sala de bingo con su configuración y creador.
     *
     * @param BingoRoomCreateDTO $dto
     * @return array
     */
    public function create(BingoRoomCreateDTO $dto): array
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

            $creatorType = $dto->creator['type'] ?? 'admin';
            $creatorId   = $dto->creator['id'] ?? $userId;

            BingoCreator::create([
                'room_id'            => $room->id,
                'creator_user_id'    => $userId,
                'creator_type'       => $creatorType,
                'creator_id'         => $creatorId,
                'creator_commn'      => $dto->roomCommn,
                'card_price'         => $dto->ctnPrice,
                'allowed_currencies' => $dto->allowedCurrencies,
                'status'             => 'ACTIVE',
            ]);

            return [
                'message' => 'Sala creada exitosamente.',
                'room_id' => $room->id,
            ];
        });
    }
}
