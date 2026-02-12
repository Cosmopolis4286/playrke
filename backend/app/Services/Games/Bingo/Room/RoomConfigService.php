<?php

namespace App\Services\Games\Bingo\Rooms;

use App\Models\Games\Bingo\Creator\BingoCreator;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Room\BingoRoomConfig;
use App\Models\Games\Common\Utils\GameConfig;
use App\Traits\Helpers\EncryptTrait;
use Carbon\Carbon;

class RoomConfigService
{
    use EncryptTrait;

    /**
     * Crea o actualiza una sala según la configuración automática.
     *
     * @param $room
     * @return BingoRoom|null
     */
    public function createOrUpdateRoom($room): ?BingoRoom
    {
        $creatorUser = $room->creator_user;
        $roomDate = $this->calculateRoomDate($room->time);

        $existingRoom = $this->existingRoom($creatorUser, $roomDate, $room->time, $room->card_price);

        if ($existingRoom && $existingRoom->Room->status >= 2) {
            return null;
        }

        $roomData = $this->prepareRoomData($room);
        $currentRoom = $existingRoom
            ? tap($existingRoom)->update($roomData)
            : BingoRoom::create($roomData);

        $this->configureRoom($currentRoom->id, $room, $roomDate);

        return $currentRoom;
    }

    /**
     * Prepara los datos base de la sala.
     */
    protected function prepareRoomData($room): array
    {
        $model = 'BingoRooms';
        $prizePool = $room->prize_pool_type > 1 ? $room->amount_added_prize_pool : 0;

        return [
            'encrypted' => $this->generateUniqueCode($model),
            'status' => 2,
            'room_type' => $room->room_type,
            'creator_user_id' => $room->creator_user,
            'prizePool' => $prizePool,
            'uuid_code' => $this->getCodeUUID($model)
        ];
    }

    /**
     * Configura la sala y su creador.
     */
    protected function configureRoom(int $roomId, $dataRoom, string $roomDate): void
    {
        $bingoRKECommission = GameConfig::getGameParamValue('BINGO', 'COMMISSION') ?? 5;
        $unixDate = Carbon::createFromFormat('Y-m-d H:i', "{$roomDate} {$dataRoom->time}")->timestamp;

        BingoRoomConfig::updateOrCreate(
            ['room' => $roomId],
            array_merge((array)$dataRoom, [
                'creation' => 'A',
                'date' => $roomDate,
                'unix_date' => $unixDate,
                'rke_commn' => $bingoRKECommission
            ])
        );

        BingoCreator::updateOrCreate(
            ['room' => $roomId],
            [
                'creator_user_id' => $dataRoom->creator_user,
                'creator_type' => $dataRoom->creator_type,
                'creator_id' => $dataRoom->creator_id,
                'creator_commn' => $dataRoom->room_commn,
                'card_price' => $dataRoom->card_price,
                'allowed_currencies' => $dataRoom->allowed_currencies
            ]
        );
    }

    /**
     * Calcula la fecha de la sala.
     */
    protected function calculateRoomDate(string $time): string
    {
        $now = Carbon::now();
        $roomTime = Carbon::parse("{$now->format('Y-m-d')} {$time}");

        return $now->copy()->addMinutes(15)->greaterThanOrEqualTo($roomTime)
            ? $now->copy()->addDay()->format('Y-m-d')
            : $now->format('Y-m-d');
    }

    /**
     * Retorna sala existente si ya fue creada.
     */
    protected function existingRoom($creatorUser, string $date, string $time, $ctnPrice)
    {
        $startDate = Carbon::parse($date)->subDay()->format('Y-m-d');

        return BingoRoomConfig::with('Room:id,status')
            ->select('room')
            ->where('creator_user_id', $creatorUser)
            ->whereBetween('date', [$startDate, $date])
            ->where('time', $time)
            ->where('card_price', $ctnPrice)
            ->first();
    }
}
