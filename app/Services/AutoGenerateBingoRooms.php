<?php

namespace App\Services;

use App\Models\BingoRoom;
use App\Models\BingoRoomAutomatic;
use App\Models\BingoRoomConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGenerateBingoRooms
{
    public function createBingoRooms()
    {
        $automaticRooms = BingoRoomAutomatic::where('status', 1)->get();

        DB::beginTransaction();

        try {

            foreach ($automaticRooms as $room) {
                # code...
            }

            BingoRoom::insert();
            BingoRoomConfig::insert();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en la JOB durante la creacion de Salas de Bingo: ' . $e->getMessage());
        }
    }
}
