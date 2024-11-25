<?php

namespace App\Services;

use App\Models\BingoRoom;
use App\Models\BingoRoomAutomatic;
use App\Models\BingoRoomConfig;
use App\Traits\EncryptRKE;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGenerateBingoRooms
{
    use EncryptRKE;

    public function createBingoRooms()
    {
        $automaticRooms = BingoRoomAutomatic::where('status', 1)->get();
    
        $dataRoom = [];
        $dataRoomConfig = [];
    
        DB::beginTransaction();
    
        try {
            $currentDate = now()->format('Y-m-d'); 
    
            foreach ($automaticRooms as $room) {

                $creatorType = $room->creator_type;
                $creatorID = $room->creator_id;
                $roomTime = $room->time;
    
                // Verificar si existe una sala con los mismos datos
                $existsRoom = BingoRoom::where($creatorType, $creatorID)
                    ->where('date', $currentDate)
                    ->where('time', $roomTime)
                    ->where('status', '>', 1)
                    ->exists();
    
                // Ajustar la fecha si ya existe una sala
                $roomDate = $existsRoom ? now()->addDay()->format('Y-m-d') : $currentDate;
    
                $publicationDate = strtotime("{$roomDate} {$roomTime}");
    
                // Preparar información principal para la sala
                $dataRoom[] = [
                    'date' => $roomDate, 
                    'time' => $roomTime,
                    'unix_date' => $publicationDate,
                    'game_mode' => $room->game_mode,
                    'ctn_price' => $room->ctn_price,
                    $creatorType => $creatorID,
                    'status' => 2,
                    'creation' => 'A',
                    'publication_date' => $publicationDate,
                    'encrypted' => $this->generateUniqueCode('bingo_rooms'),
                    'room_type' => $room->room_type,
                ];
            }
    
            // Inserción masiva de las salas
            $createdRooms = BingoRoom::insert($dataRoom);
    
            // Preparar configuraciones para las salas creadas
            foreach ($dataRoom as $index => $room) {
                $dataRoomConfig[] = [
                    'room' => $createdRooms[$index]['id'], // Obtener el ID generado
                    'jackpot_type' => $automaticRooms[$index]->jackpot_type,
                    'fixed_jackpot_value' => $automaticRooms[$index]->fixed_jackpot_value,
                    'room_commn' => $automaticRooms[$index]->room_commn,
                    'serials_commn' => $automaticRooms[$index]->serials_commn,
                    'val_serials' => $automaticRooms[$index]->val_serials,
                    'num_serials' => $automaticRooms[$index]->num_serials,
                ];
            }
    
            // Inserción masiva de configuraciones
            BingoRoomConfig::insert($dataRoomConfig);
    
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en la creación de salas de bingo: ' . $e->getMessage());
        }
    }    
}
