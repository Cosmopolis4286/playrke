<?php

namespace App\Services\Game\Bingo;

use App\Models\{BingoCreator, BingoCtnPrice, BingoCtnPriceDrafts, BingoRoom, BingoRoomAutomatic, BingoRoomConfig, BingoRounds, BingoRoundsDrafts, GameConfig};
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

                $creatorUser = $room->creator_user;

                // Calcular fecha y hora de la sala
                $roomTime = Carbon::parse("{$now->format('Y-m-d')} {$room->time}");
                $roomDate = $this->calculateRoomDate($roomTime, $now);

                // Verificar si la sala ya existe
                $existingRoom = $this->existingRoom($creatorUser, $roomDate, $room->time, $room->ctn_price);

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
                $automaticBingoID = $room->id;

                // Configurar la sala
                $this->configureRoom($roomID, $room, $roomDate);

                // Asociar precios de monedas a la sala
                $this->associateCtnPrices($automaticBingoID, $roomID, $creatorUser);

                if ($room->rounds > 1 && $room->rounds_pct) {
                    // Asociar rondas a la sala
                    $this->associateRounds($automaticBingoID, $roomID);
                }
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

        $amountAddedPrizePool = $room->amount_added_prize_pool;
        $boat = $room->prize_pool_type > 1 ? $amountAddedPrizePool : 0;

        return [
            'encrypted' => $this->generateUniqueCode($model),
            'status' => 2,
            'room_type' => $room->room_type,
            'creator_user' => $room->creator_user,
            'prizePool' => $boat,
            'code_uuid' => $this->getCodeUUID($model)
        ];
    }

    /**
     * Configura una sala con sus detalles.
     */
    private function configureRoom($roomID, $dataRoom, $roomDate)
    {
        $bingoRKECommission = GameConfig::getGameParamValue('BINGO', 'COMMISSION') ?? 5;

        $unixDate = Carbon::createFromFormat('Y-m-d H:i', "{$roomDate} {$dataRoom->time}")->timestamp;

        BingoRoomConfig::updateOrCreate(
            ['room' => $roomID],
            [
                'creation' => 'A',
                'creator_user' => $dataRoom->creator_user,
                'game_mode' => $dataRoom->game_mode,
                'date' => $roomDate,
                'time' => $dataRoom->time,
                'unix_date' => $unixDate,
                'ctn_price' => $dataRoom->ctn_price,
                'allowed_coins' => $dataRoom->allowed_coins,
                'rke_commn' => $bingoRKECommission,
                'room_commn' => $dataRoom->room_commn,
                'prize_pool_type' => $dataRoom->prize_pool_type,
                'amount_added_prize_pool' => $dataRoom->amount_added_prize_pool,
                'time_chip' => $dataRoom->time_chip,
                'rounds' => $dataRoom->rounds,
                'rounds_pct' => $dataRoom->rounds_pct,
                'serials_pct' => $dataRoom->serials_pct,
                'num_serials' => $dataRoom->num_serials,
                'jackpot_pct' => $dataRoom->jackpot_pct,
            ]
        );

        $bingoCreatorID = BingoCreator::updateOrCreate(
            ['room' => $roomID],
            [
                'creator_user' => $dataRoom->creator_user,
                'creator_type' => $dataRoom->creator_type,
                'creator_id' => $roomDate->creator_id,
                'creator_commn' => $dataRoom->room_commn,
                'ctn_price' => $dataRoom->ctn_price,
                'allowed_coins' => $dataRoom->allowed_coins
            ]
        )->id;

        return $bingoCreatorID;
    }

    /**
     * Asocia los precios de monedas (ctn) a una sala.
     */
    private function associateCtnPrices($automaticBingoID, $roomID, $creatorUser)
    {
        $bingoCtnPrice = BingoCtnPriceDrafts::getByAutomaticRoom($automaticBingoID, ['coins', 'ctn_prices']);

        if (!$bingoCtnPrice) return;

        $ctnPriceCoins = $bingoCtnPrice->coins;
        $ctnPriceValue = $bingoCtnPrice->ctn_prices;

        if (is_array($ctnPriceCoins) && is_array($ctnPriceValue) && count($ctnPriceCoins) === count($ctnPriceValue)) {
            for ($i = 0; $i < count($ctnPriceCoins); $i++) {
                BingoCtnPrice::updateOrCreate(
                    ['room' => $roomID, 'coin' => $ctnPriceCoins[$i]],
                    [
                        'creator_user' => $creatorUser,
                        'ctn_price' => $ctnPriceValue[$i],
                    ]
                );
            }
        }

        return true;
    }

    /**
     * Asocia las rondas a una sala.
     */
    private function associateRounds($automaticBingoID, $roomID)
    {
        try {
            $data = BingoRoundsDrafts::getByAutomaticRoom($automaticBingoID, [
                'rounds',
                'prize_types',
                'play_types',
                'prize_values'
            ]);

            if (!$data) {
                throw new \Exception("No draft data found for automatic bingo ID {$automaticBingoID}");
            }

            BingoRounds::create([
                'room' => $roomID,
                'rounds' => $data->rounds,
                'prize_types' => $data->prize_types,
                'play_types' => $data->play_types,
                'prize_values' => $data->prize_values,
                'status' => 'ACTIVE',
            ]);

            BingoRoundsDrafts::where('automatic_bingo_id', $automaticBingoID)
                ->update(['status' => 'COMPLETED']);
        } catch (\Exception $e) {
            Log::error('Error en associateRounds: ' . $e->getMessage());
        }
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
