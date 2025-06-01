<?php

namespace App\Services\Game\Bingo;

use App\Models\{BingoCtns, BingoDraws, BingoPrizeCtns, BingoRoom, BingoRounds, BingoRoomUser, User};
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

            if (isset($winChip['Error'])) {
                $status = $winChip;
            }

            $status = ($winChip->time <= time()) ? 4 : 3;
        }

        if (!isset($status['Error'])) {
            BingoRoom::where('id', $roomID)->update(['status' => $status]);

            if ($status == 4) {
                BingoRounds::where('room', $roomID)->where('status', 'IN PROGRESS')
                    ->update(['status' => 'COMPLETED']);
            }

            $this->determineRoundsStatus($roomID);
        } else {
            Log::info($status['Error'] . ": Sala [ID => $roomID]");
        }
    }

    public function handleFinishedRooms($creatorUser = false): void
    {
        BingoRoom::with('Config:room,date,time')->where('status', 4)
            ->when($creatorUser, fn($query) => $query->where('creator_user', $creatorUser))
            ->get(['id', 'creator_user'])->each(fn($room) => $this->processFinishedRoom($room));
    }

    private function processFinishedRoom($room): void
    {
        $this->determineRoundsStatus($room->id);

        if (!BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 1)) {
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

    private function determineRoundsStatus(int $roomID): void
    {
        date_default_timezone_set(config('app.timezone'));

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

                // Activar esta ronda
                BingoRounds::where('id', $round->id)
                    ->update(['status' => 'IN PROGRESS']);

                // Solo activar una ronda por ciclo
                break;
            }
        }
    }
}
