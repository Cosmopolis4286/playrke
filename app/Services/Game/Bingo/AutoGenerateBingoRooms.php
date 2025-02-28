<?php

namespace App\Services\Game\Bingo;

use App\Models\{BingoCreator, BingoCtnPrice, BingoCtnPriceAutomatic, BingoRoom, BingoRoomAutomatic, BingoRoomConfig, UserType};
use App\Traits\EncryptRKE;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoGenerateBingoRooms
{
    use EncryptRKE;

    public function createBingoRooms($creator = '')
    {
        // Crear consulta base
        $query = BingoRoomAutomatic::where('status', 1);

        // Filtrar por creador si se proporciona
        if ($creator) {
            $query->where('creator_type', $creator['type'])
                ->where('creator_id', $creator['id']);
        }

        $automaticRooms = $query->get();

        // Verificar si hay configuraciones activas
        if ($automaticRooms->isEmpty()) {
            Log::info('No hay configuraciones activas para salas automáticas');
            return ['Info' => 'No hay configuraciones para salas automáticas creadas/activas'];
        }

        $now = Carbon::now();
        DB::beginTransaction();

        try {
            foreach ($automaticRooms as $room) {
                // Calcular fecha y hora de la sala
                $roomTime = Carbon::parse("{$now->format('Y-m-d')} {$room->time}");
                $roomDate = $this->calculateRoomDate($roomTime, $now);

                // Verificar si la sala ya existe
                $existingRoom = $this->existingRoom($room->user, $roomDate, $room->time, $room->ctn_price);

                // Saltar si la sala existente tiene un estado >= 2
                if ($existingRoom && $existingRoom->Room->status >= 2) {
                    continue;
                }

                // Preparar datos de la sala
                $roomData = $this->prepareRoomData($room);

                // Crear o actualizar la sala
                $currentRoom = $existingRoom
                    ? tap($existingRoom)->update($roomData)
                    : BingoRoom::create($roomData);

                $roomID = $currentRoom->id;

                // Configurar la sala y retornar bingo_creator_id
                $bingoCreatorID = $this->configureRoom($roomID, $room, $roomDate);

                // Asociar precios de monedas a la sala
                $this->associateCtnPrices($room->id, $roomID, $bingoCreatorID);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear salas automáticas: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepara los datos para una sala.
     */
    private function prepareRoomData($room)
    {
        $model = 'BingoRooms';

        $fixedJackpotValue = $room->fixed_jackpot_value;
        $boat = $room->jackpot_type > 1 ? $fixedJackpotValue : 0;

        return [
            'encrypted' => $this->generateUniqueCode($model),
            'status' => 2,
            'room_type' => $room->room_type,
            'creator_user' => $room->creator_user,
            'jackpot' => $boat,
            'code_uuid' => $this->getCodeUUID($model)
        ];
    }

    /**
     * Configura una sala con sus detalles.
     */
    private function configureRoom($roomID, $dataRoom, $roomDate)
    {
        $unixDate = Carbon::createFromFormat('Y-m-d H:i', "{$roomDate} {$dataRoom->time}")->timestamp;

        BingoRoomConfig::updateOrCreate(
            ['room' => $roomID],
            [
                'creation' => 'A',
                'game_mode' => $dataRoom->game_mode,
                'date' => $roomDate,
                'time' => $dataRoom->time,
                'unix_date' => $unixDate,
                'ctn_price' => $dataRoom->ctn_price,
                'room_commn' => $dataRoom->room_commn,
                'jackpot_type' => $dataRoom->jackpot_type,
                'fixed_jackpot_value' => $dataRoom->fixed_jackpot_value,
                'time_chip' => $dataRoom->time_chip,
                'pct_serials' => $dataRoom->pct_serials,
                'num_serials' => $dataRoom->num_serials,
                'serials_commn' => $dataRoom->serials_commn,
            ]
        );

        $bingoCreatorID = BingoCreator::updateOrCreate(
            ['room' => $roomID],
            [
                'creator_type' => $dataRoom->creator_type,
                'creator_id' => $roomDate->creator_id,
                'creator_commn' => $dataRoom->room_commn,
                'ctn_price' => $dataRoom->ctn_price,
            ]
        )->id;

        return $bingoCreatorID;
    }

    /**
     * Asocia los precios de monedas (ctn) a una sala.
     */
    private function associateCtnPrices($automaticBingoID, $roomID, $bingoCreatorID)
    {
        $coinsCtnPrice = BingoCtnPriceAutomatic::getOnlyCoinsCtnPrice($automaticBingoID ?? '');

        if (!$coinsCtnPrice) return;

        foreach ($coinsCtnPrice as $key => $ctnPrice) {
            BingoCtnPrice::updateOrCreate(
                ['room' => $roomID, 'coin' => $key],
                [
                    'bingo_creator_id' => $bingoCreatorID,
                    'ctn_price' => $ctnPrice,
                ]
            );
        }

        return true;
    }

    private function calculateRoomDate($roomTime, $now)
    {
        return $now->addMinutes(15)->greaterThanOrEqualTo($roomTime)
            ? $now->copy()->addDay()->format('Y-m-d')
            : $now->format('Y-m-d');
    }

    private function existingRoom($creatorUser, $date, $time, $ctnPrice)
    {
        $startDate = Carbon::parse($date)->subDay()->format('Y-m-d');

        return BingoRoomConfig::with('Room:id,status')->select('room')
            ->where('creator_user', $creatorUser)->whereBetween('date', [$startDate, $date])
            ->where('time', $time)->where('ctn_price', $ctnPrice)->first();
    }
}
