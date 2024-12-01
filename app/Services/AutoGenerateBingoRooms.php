<?php

namespace App\Services;

use App\Models\BingoCreator;
use App\Models\BingoRoom;
use App\Models\BingoRoomAutomatic;
use App\Models\BingoRoomConfig;
use App\Models\UserType;
use App\Traits\EncryptRKE;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGenerateBingoRooms
{
    use EncryptRKE;

    public function createBingoRooms($creator = '')
    {
        $query = BingoRoomAutomatic::where('status', 1);

        if ($creator) {
            $query->where('creator_type', $creator['type'])
                ->where('creator_id', decrypt($creator['id']));
        }

        $automaticRooms = $query->get();

        if ($automaticRooms->isEmpty()) {
            return false;
        }

        $dataRoomConfig = [];

        DB::beginTransaction();

        try {

            $timestamp = now();
            $currentDate = $timestamp->format('Y-m-d');

            foreach ($automaticRooms as $room) {

                $creatorType = UserType::getValueById($room->creator_type, 'prefix');
                $creatorID = $room->creator_id;

                $roomTime = $room->time;
                $roomCtnPrice = $room->ctn_price;

                $existingRoomToday = $this->existingRoom($creatorType, $creatorID, $currentDate, $roomTime, $roomCtnPrice);

                $roomDate = $existingRoomToday ? $timestamp->copy()->addDay()->format('Y-m-d') : $currentDate;

                // Verificar si existe una sala al día siguiente con las mismas condiciones
                if ($existingRoomToday) {
                    if ($this->existingRoom($creatorType, $creatorID, $roomDate, $roomTime, $roomCtnPrice)) {
                        continue; // Saltar si ya existe la sala
                    }
                }

                // Verificar si el horario esta disponible
                $unixDate = strtotime("{$roomDate} {$roomTime}");

                if (time() + 900 >= $unixDate) {
                    continue;
                }

                $roomData = [
                    'date' => $roomDate,
                    'time' => $roomTime,
                    'status' => 2,
                    'unix_date' => $unixDate,
                    'publication_date' => "{$roomDate} {$roomTime}",
                    'game_mode' => $room->game_mode,
                    'status' => 2,
                    'encrypted' => $this->generateUniqueCode('bingo_rooms'),
                    'room_type' => $room->room_type,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                // Insertar una sala y obtener su ID
                $roomId = BingoRoom::insertGetId($roomData);

                // Agregar configuración asociada a la sala creada
                $dataRoomConfig = [
                    'room' => $roomId,
                    'creation' => 'A',
                    'game_mode' => $room->game_mode,
                    'room_type' => $room->room_type,
                    'jackpot_type' => $room->jackpot_type,
                    'fixed_jackpot_value' => $room->fixed_jackpot_value,
                    'room_commn' => $room->room_commn,
                    'serials_commn' => $room->serials_commn,
                    'val_serials' => $room->val_serials,
                    'num_serials' => $room->num_serials,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                // Insertar datos de la configuracion de la sala
                BingoRoomConfig::insert($dataRoomConfig);

                // Insertar informacion del creador de la sala
                BingoCreator::insert([
                    'room' => $roomId,
                    'creator_type' => $room->creator_type,
                    'creator_id' => $creatorID,
                    'creator_commn' => $room->room_commn,
                    'ctn_price' => $roomCtnPrice,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en la creación de salas de bingo: ' . $e->getMessage());
            return false;
        }
    }

    private function existingRoom($creatorType, $creatorID, $date, $time, $ctnPrice)
    {
        return BingoRoom::with([
            'Creator' => function ($query) use ($creatorType, $creatorID, $ctnPrice) {
                $query->select('room', 'creator_type', 'creator_id', 'ctn_price')
                    ->where([
                        ['creator_type', $creatorType],
                        ['creator_id', $creatorID],
                        ['ctn_price', $ctnPrice]
                    ]);
            },
        ])->where('date', $date)->where('time', $time)->where('status', '>', 1)->exists();
    }
}
