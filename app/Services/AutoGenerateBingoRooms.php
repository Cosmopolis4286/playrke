<?php

namespace App\Services;

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
                ->where('creator_id', $creator['id']);
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
                    'unix_date' => $unixDate,
                    'game_mode' => $room->game_mode,
                    'ctn_price' => $roomCtnPrice,
                    $creatorType => $creatorID,
                    'status' => 2,
                    'creation' => 'A',
                    'publication_date' => "{$roomDate} {$roomTime}",
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
                    'jackpot_type' => $room->jackpot_type,
                    'fixed_jackpot_value' => $room->fixed_jackpot_value,
                    'room_commn' => $room->room_commn,
                    'serials_commn' => $room->serials_commn,
                    'val_serials' => $room->val_serials,
                    'num_serials' => $room->num_serials,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                BingoRoomConfig::insert($dataRoomConfig);
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
        return BingoRoom::where($creatorType, $creatorID)
            ->where('date', $date)
            ->where('time', $time)
            ->where('ctn_price', $ctnPrice)
            ->where('status', '>', 1)
            ->exists();
    }
}
