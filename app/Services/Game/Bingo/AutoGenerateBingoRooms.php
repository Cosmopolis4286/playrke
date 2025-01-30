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
                $creatorType = UserType::getValueById($room->creator_type, 'prefix');
                $creatorID = $room->creator_id;

                // Calcular fecha y hora de la sala
                $roomTime = Carbon::parse("{$now->format('Y-m-d')} {$room->time}");
                $roomDate = $this->calculateRoomDate($roomTime, $now);

                // Verificar si la sala ya existe
                $existingRoom = $this->existingRoom($creatorType, $creatorID, $roomDate, $room->time, $room->ctn_price);

                // Saltar si la sala existente tiene un estado >= 2
                if ($existingRoom && $existingRoom->status >= 2) {
                    continue;
                }

                // Preparar datos de la sala
                $roomData = $this->prepareRoomData($room, $roomDate, $creatorType, $creatorID);

                // Crear o actualizar la sala
                $roomID = $existingRoom
                    ? tap($existingRoom)->update($roomData)->id
                    : BingoRoom::create($roomData)->id;

                // Configurar la sala y retornar bingo_creator_id
                $bingoCreatorID = $this->configureRoom($roomID, $room, $creatorID);

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
    private function prepareRoomData($room, $roomDate, $creatorType, $creatorID)
    {
        return [
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
    }

    /**
     * Configura una sala con sus detalles.
     */
    private function configureRoom($roomID, $room, $creatorID)
    {
        BingoRoomConfig::updateOrCreate(
            ['room' => $roomID],
            [
                'jackpot_type' => $room->jackpot_type,
                'fixed_jackpot_value' => $room->fixed_jackpot_value,
                'room_commn' => $room->room_commn,
                'serials_commn' => $room->serials_commn,
                'val_serials' => $room->val_serials,
                'num_serials' => $room->num_serials,
            ]
        );

        $bingoCreatorID = BingoCreator::updateOrCreate(
            ['room' => $roomID],
            [
                'creator_type' => $room->creator_type,
                'creator_id' => $creatorID,
                'creator_commn' => $room->room_commn,
                'ctn_price' => $room->ctn_price,
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
