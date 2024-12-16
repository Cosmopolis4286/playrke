<?php

namespace App\Services\Game\Bingo;

use App\Models\{BingoCreator, BingoRoom, BingoRoomAutomatic, BingoRoomConfig, UserType};
use App\Traits\EncryptRKE;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoGenerateBingoRooms
{
    use EncryptRKE;

    public function createBingoRooms($creator = '')
    {
        $query = BingoRoomAutomatic::where('status', 1);

        if ($creator) {
            $query->where('creator_type', $creator['type'])
                ->where('creator_id', $creator['id']);
        }

        $automaticRooms = $query->get();
        if ($automaticRooms->isEmpty()) {
            Log::info('No hay configuraciones activas para salas automáticas');
            return ['Info' => 'No hay configuraciones para salas automáticas creadas/activas'];
        }

        $now = Carbon::now();
        DB::beginTransaction();

        try {
            foreach ($automaticRooms as $room) {
                $creatorType = UserType::getValueById($room->creator_type, 'prefix');
                $creatorID = $room->creator_id;

                $roomTime = Carbon::parse("{$now->format('Y-m-d')} {$room->time}");
                $roomDate = $this->calculateRoomDate($roomTime, $now);

                $existingRoom = $this->existingRoom($creatorType, $creatorID, $roomDate, $room->time, $room->ctn_price);

                if ($existingRoom && $existingRoom->status >= 2) {
                    continue;
                }

                $roomData = [
                    'date' => $roomDate,
                    'time' => $room->time,
                    'unix_date' => Carbon::createFromFormat('Y-m-d H:i', "{$roomDate} {$room->time}")->timestamp,
                    'game_mode' => $room->game_mode,
                    'ctn_price' => $room->ctn_price,
                    $creatorType => $creatorID,
                    'status' => 2,
                    'creation' => 'A',
                    'publication_date' => "{$roomDate} {$room->time}",
                    'encrypted' => $this->generateUniqueCode('bingo_rooms'),
                    'room_type' => $room->room_type,
                ];

                $roomId = $existingRoom
                    ? tap($existingRoom)->update($roomData)->id
                    : BingoRoom::create($roomData)->id;

                BingoRoomConfig::updateOrCreate(
                    ['room' => $roomId],
                    [
                        'jackpot_type' => $room->jackpot_type,
                        'fixed_jackpot_value' => $room->fixed_jackpot_value,
                        'room_commn' => $room->room_commn,
                        'serials_commn' => $room->serials_commn,
                        'val_serials' => $room->val_serials,
                        'num_serials' => $room->num_serials,
                    ]
                );

                BingoCreator::updateOrCreate(
                    ['room' => $roomId],
                    [
                        'creator_type' => $room->creator_type,
                        'creator_id' => $creatorID,
                        'creator_commn' => $room->room_commn,
                        'ctn_price' => $room->ctn_price,
                    ]
                );
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    private function calculateRoomDate($roomTime, $now)
    {
        return $now->addMinutes(15)->greaterThanOrEqualTo($roomTime)
            ? $now->copy()->addDay()->format('Y-m-d')
            : $now->format('Y-m-d');
    }

    private function existingRoom($creatorType, $creatorID, $date, $time, $ctnPrice)
    {
        $startDate = Carbon::parse($date)->subDay()->format('Y-m-d');

        return BingoRoom::where($creatorType, $creatorID)
            ->whereBetween('date', [$startDate, $date])
            ->where('time', $time)
            ->where('ctn_price', $ctnPrice)
            ->first();
    }
}
