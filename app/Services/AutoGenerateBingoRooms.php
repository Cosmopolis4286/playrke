<?php

namespace App\Services;

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
            return ['Info' => 'No hay configuraciones para salas automáticas creadas/activas'];
        }

        $currentDate = Carbon::now();
        DB::beginTransaction();

        try {
            foreach ($automaticRooms as $room) {
                $creatorType = UserType::getValueById($room->creator_type, 'prefix');
                $creatorID = $room->creator_id;

                // Calcula la fecha de la sala
                $roomTime = Carbon::parse("{$currentDate->format('Y-m-d')} {$room->time}");
                $addDay = Carbon::now()->addMinutes(15)->greaterThanOrEqualTo($roomTime);
                $roomDate = $addDay ? $currentDate->copy()->addDay()->format('Y-m-d') : $currentDate->format('Y-m-d');

                // Verifica si existe una sala activa
                $existingRoom = $this->existingRoom($creatorType, $creatorID, $roomDate, $room->time, $room->ctn_price);

                if ($existingRoom && $existingRoom->status >= 2) {
                    continue;
                }

                // Datos principales de la sala
                $roomData = [
                    'date' => $roomDate,
                    'time' => $room->time,
                    'unix_date' => $roomTime->timestamp,
                    'game_mode' => $room->game_mode,
                    'ctn_price' => $room->ctn_price,
                    $creatorType => $creatorID,
                    'status' => 2,
                    'creation' => 'A',
                    'publication_date' => "{$roomDate} {$room->time}",
                    'encrypted' => $this->generateUniqueCode('bingo_rooms'),
                    'room_type' => $room->room_type,
                ];

                // Inserta o actualiza la sala
                $roomId = $existingRoom
                    ? tap($existingRoom)->update($roomData)->id
                    : BingoRoom::create($roomData)->id;

                // Configuración asociada
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

                // Información del creador
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
            Log::info('Nueva Sala Creada Automáticamente', [
                'creator_type' => $room->creator_type,
                'creator_id' => $creatorID,
                'date' => $roomDate,
                'time' => $room->time,
                'ctn_price' => $room->ctn_price
            ]);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en la creación de salas de bingo', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    private function existingRoom($creatorType, $creatorID, $date, $time, $ctnPrice)
    {
        return BingoRoom::where($creatorType, $creatorID)
            ->where('date', $date)
            ->where('time', $time)
            ->where('ctn_price', $ctnPrice)
            ->first();
    }
}
