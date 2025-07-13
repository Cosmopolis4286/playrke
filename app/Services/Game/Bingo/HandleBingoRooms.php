<?php

namespace App\Services\Game\Bingo;

use App\Models\{BingoCtnPrice, BingoCtnPriceDrafts, BingoCtns, BingoDraws, BingoPrizeCtns, BingoRoom, BingoRounds, BingoRoundsDrafts, User};
use App\Traits\BingoTrait;
use Illuminate\Support\Facades\Log;

class HandleBingoRooms
{
    use BingoTrait;

    public function checkRoomClosure($room = '', bool $creatorUser = false): void
    {
        BingoRoom::with('Config:room,unix_date')->whereIn('status', [2, 3]) // Filtramos por status
            ->when($creatorUser, fn($query) => $query->where('creator_user', $creatorUser)) // Condición para creator
            ->when($room, fn($query) => $query->where('id', $room)) // Condición para room
            ->get('id')->each(fn($room) => $this->processRoomClosure($room)); // Procesar cada sala

        $this->handleFinishedRooms($creatorUser);
    }

    private function processRoomClosure($room): void
    {
        date_default_timezone_set(config('app.timezone'));

        if (time() < $room->Config->unix_date) {
            return;
        }

        $roomID = $room->id;
        $status = 1; // Status 1: Borrador
        $existsCtns = BingoCtns::getCountCtnByRoom($roomID);

        if ($existsCtns) {
            $winChip = BingoDraws::getWinningChip($roomID) ?? $this->handleDraw($roomID);

            dd($winChip);

            if (isset($winChip['Error'])) {
                Log::info($winChip['Error'] . ": Sala [ID => $roomID]");
                return;
            }

            $status = ($winChip->time <= time()) ? 4 : 3;

            BingoRoom::where('id', $roomID)->update(['status' => $status]);
            $this->determineRoundsStatus($roomID, $status);
            $this->registerCtnPrices($roomID);
        }

        BingoRoom::where('id', $roomID)->update(['status' => $status]);
    }

    public function handleFinishedRooms($creatorUser = false): void
    {
        BingoRoom::with('Config:room,date,time')->where('status', 4)
            ->when($creatorUser, fn($query) => $query->where('creator_user', $creatorUser))
            ->get(['id', 'creator_user'])->each(fn($room) => $this->processFinishedRoom($room));
    }

    private function processFinishedRoom($room): void
    {
        $this->determineRoundsStatus($room->id, 4);

        if (!BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 'Cartón Lleno')) {
            $this->handleWinners($room->id);
        } else {
            $this->allWinCtnPaid($room->id);
        }

        $creatorUser = $room->creator_user;
        $creator = User::getCreatorUser($creatorUser, false, true);

        if ($creator['type'] === 'commerce') {
            // Actulizar estado de los cartones ganadores
            BingoPrizeCtns::where('room', $room->id)->update(['active' => 'Y']);
        } else if ($creator['type'] === 'customer') {
            // Verificar si existen pagos que procesar
            (new PrizeCartonService())->handleCustomerPrizeCarton($creatorUser);
        }

        $this->checkAndHideRoom($room);
    }

    private function checkAndHideRoom($room): void
    {
        date_default_timezone_set(config('app.timezone'));

        $dataTime2Week = strtotime("{$room->Config->date} {$room->Config->time}") + (2 * 7 * 24 * 60 * 60); // Sumar 2 semanas

        if ($dataTime2Week <= time()) {
            BingoRoom::where('id', $room->id)->update(['status' => 6]); // Status 6: Sala Oculta
        }
    }

    private function determineRoundsStatus(int $roomID, int $roomStatus): void
    {
        date_default_timezone_set(config('app.timezone'));

        if ($roomStatus === 3) {
            $this->associateRounds($roomID);
        }

        // Obtener todas las rondas pendientes (no completadas) de la sala
        $rounds = BingoRounds::where('room', $roomID)
            ->where('status', '<>', 'COMPLETED')
            ->orderByDesc('id')->get(['id', 'round']);

        if ($rounds->isEmpty()) {
            return;
        }

        // Mapear tiempos de los draws por ronda
        $drawTimes = BingoDraws::where('room', $roomID)
            ->whereIn('round', $rounds->pluck('round'))
            ->where('status', 3)->pluck('time', 'round');

        foreach ($rounds as $round) {

            $drawTime = $drawTimes[$round->round] ?? null;

            if ($drawTime && $drawTime <= time()) {
                // Completar cualquier otra ronda que esté en progreso
                BingoRounds::where('room', $roomID)
                    ->where('id', '!=', $round->id)
                    ->where('status', 'IN PROGRESS')
                    ->update(['status' => 'COMPLETED']);

                $newStatus = $roomStatus == 4 ? 'COMPLETED' : 'IN PROGRESS';

                BingoRounds::where('id', $round->id)
                    ->update(['status' => $newStatus]);

                break;
            }
        }
    }

    /**
     * Asocia las rondas a una sala.
     */
    private function associateRounds($roomID)
    {
        try {
            $rounds = BingoRounds::where('room', $roomID)->exists();

            if ($rounds) return true;

            $bingoRounds = BingoRoundsDrafts::getByRoom($roomID, [
                'rounds',
                'prize_types',
                'play_types',
                'prize_values'
            ]);

            if (!$bingoRounds) {
                throw new \Exception("No draft data found for bingo ID {$roomID}");
            }

            BingoRounds::create([
                'room' => $roomID,
                'rounds' => $bingoRounds->rounds,
                'prize_types' => $bingoRounds->prize_types,
                'play_types' => $bingoRounds->play_types,
                'prize_values' => $bingoRounds->prize_values,
                'status' => 'ACTIVE',
            ]);

            $bingoRounds->update(['status' => 'COMPLETED']);

            return true;
        } catch (\Exception $e) {
            Log::error('Error en associateRounds: ' . $e->getMessage());
        }
    }

    private function registerCtnPrices(int $roomID)
    {
        try {
            $bingoCtnPrice = BingoCtnPriceDrafts::getByRoom($roomID, ['creator_user', 'coins', 'ctn_prices', 'status']);

            if (!$bingoCtnPrice) {
                return false;
            }

            $coins = $bingoCtnPrice->coins ?? [];
            $prices = $bingoCtnPrice->ctn_prices ?? [];

            if (!is_array($coins) || !is_array($prices) || count($coins) !== count($prices)) {
                throw new \Exception('Datos de monedas y precios inconsistentes.');
            }

            foreach ($coins as $index => $coin) {
                BingoCtnPrice::updateOrCreate(
                    ['room' => $roomID, 'coin' => $coin],
                    [
                        'creator_user' => $bingoCtnPrice->creator_user,
                        'ctn_price' => $prices[$index],
                    ]
                );
            }

            $bingoCtnPrice->update(['status' => 'COMPLETED']);

            return true;
        } catch (\Exception $e) {
            Log::error('Error en registerCtnPrices: ' . $e->getMessage(), [
                'roomID' => $roomID,
                'creatorUser' => $bingoCtnPrice->creator_user,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
