<?php

namespace App\Services\Game\Polla;

use App\Models\{BingoCtns, BingoDraw, BingoPrizeCtns, BingoRoom, BingoRoomUser};
use App\Traits\BingoTrait;

class HandlePollaDraws
{
    use BingoTrait;

    public function checkRoomClosure(int $room = null, array $creator = []): void
    {
        $fields = ['id', 'unix_date'];
        if (!empty($creator)) {
            $fields[] = $creator['type'];
        }

        BingoRoom::whereIn('status', [2, 3]) // Filtramos por status
            ->when(!empty($creator), fn($query) => $query->where($creator['type'], $creator['id'])) // Condición para creator
            ->when($room, fn($query) => $query->where('id', $room)) // Condición para room
            ->get($fields) // Obtener solo los campos necesarios
            ->each(fn($room) => $this->processRoomClosure($room)); // Procesar cada sala

        $this->handleFinishedRooms($creator);
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
            $winChip = BingoDraw::getWinningChip($roomID) ?? $this->handleDraw($roomID);
            return ($winChip->time <= time()) ? 4 : 3;
        }

        return 1; // Status 1: Borrador
    }

    public function handleFinishedRooms(array $creator = []): void
    {
        $fields = ['id', 'date', 'time', 'commerce', 'customer'];
        if (!empty($creator)) {
            $fields[] = $creator['type'];
        }

        BingoRoom::where('status', 4) // Filtramos por el Status
            ->when(!empty($creator), fn($query) => $query->where($creator['type'], $creator['id']))
            ->get($fields)->each(fn($room) => $this->processFinishedRoom($room));
    }

    private function processFinishedRoom($room): void
    {
        if (!BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 1)) {
            $this->handleJackpotWinners($room->id);
        }

        if ($room->commerce) {
            // Actulizar estado de los cartones ganadores
            BingoPrizeCtns::where('room', $room->id)->update(['active' => 'Y']);
        } else if ($room->customer) {
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
