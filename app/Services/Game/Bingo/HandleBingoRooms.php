<?php

namespace App\Services\Game\Bingo;

use App\Models\{BingoCtns, BingoDraw, BingoPrizeCtns, BingoRoom, BingoRoomUser};
use App\Traits\BingoTrait;

class HandleBingoRooms
{
    use BingoTrait;

    public function checkRoomClosure($room = null): void
    {
        date_default_timezone_set(config('app.timezone'));

        BingoRoom::whereIn('status', [2, 3])
            ->when($room, fn($query) => $query->where('id', $room))
            ->get(['id', 'unix_date'])
            ->each(fn($room) => $this->processRoomClosure($room));

        $this->handleFinishedRooms();
    }

    private function processRoomClosure($room): void
    {
        if (time() < $room->unix_date) {
            return;
        }

        $roomID = $room->id;
        $existsCtns = BingoCtns::getCountCtnByRoom($roomID);
        $existsPlayers = BingoRoomUser::getPlayerCountByRoom($roomID);

        $status = $this->determineRoomStatus($roomID, $existsCtns, $existsPlayers);

        BingoRoom::where('id', $roomID)->update(['status' => $status]);
    }

    private function determineRoomStatus(int $roomID, bool $existsCtns, bool $existsPlayers): int
    {
        if ($existsCtns && $existsPlayers) {
            $winChip = BingoDraw::getWinningChip($roomID) ?? $this->newDraw($roomID);
            return ($winChip->time <= time()) ? 4 : 3;
        }

        return 1; // Status 1: Borrador
    }

    private function handleFinishedRooms(): void
    {
        BingoRoom::where('status', 4)
            ->get(['id', 'customer', 'date', 'time'])
            ->each(fn($room) => $this->processFinishedRoom($room));
    }

    private function processFinishedRoom($room): void
    {
        if (!BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 1)) {
            $this->handleJackpotWinners($room->id);
        }

        if ($room->customer) {
            // Verificar si existen pagos que procesar
            (new PrizeCartonService())->handleCustomerPrizeCarton($room->customer);
        }

        $this->checkAndHideRoom($room);
    }

    private function checkAndHideRoom($room): void
    {
        $dataTime2Week = strtotime("{$room->date} {$room->time}") + (2 * 7 * 24 * 60 * 60); // Sumar 2 semanas

        if ($dataTime2Week <= time()) {
            BingoRoom::where('id', $room->id)->update(['status' => 6]); // Status 6: Sala Oculta
        }
    }
}
