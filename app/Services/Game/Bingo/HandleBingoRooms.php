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
        $existsCtns = BingoCtns::getCountCtnByRoom($roomID);
        $existsPlayers = BingoRoomUser::getPlayerCountByRoom($roomID);

        $status = $this->determineRoomStatus($roomID, $existsCtns, $existsPlayers);

        if (!isset($status['Error'])) {
            BingoRoom::where('id', $roomID)->update(['status' => $status]);
            $this->determineRoundsStatus($roomID);
        } else {
            Log::info($status['Error'] . ": Sala [ID => $roomID]");
        }
    }

    private function determineRoomStatus(int $roomID, bool $existsCtns, bool $existsPlayers): int
    {
        date_default_timezone_set(config('app.timezone'));

        if ($existsCtns && $existsPlayers) {

            $winChip = BingoDraws::getWinningChip($roomID) ?? $this->handleDraw($roomID);

            if (isset($winChip['Error'])) {
                return $winChip;
            }

            return ($winChip->time <= time()) ? 4 : 3;
        }

        return 1; // Status 1: Borrador
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

        $rounds = BingoRounds::where('room', $roomID)
            ->where('status', '<>', 'COMPLETED')
            ->get(['id', 'round', 'status']);

        if (!count($rounds)) return;

        foreach ($rounds as $item) {

            $drawTime = BingoDraws::select('id', 'time')->where('room', $roomID)
                ->where('round', 4)->where('status', 3)->first();

            dd($drawTime);

            if ($drawTime->time <= time()) {
                $status = 'IN PROGRESS';
                BingoRounds::where('id', $item->id)->update(['status' => $status]);
            }
        }
    }
}
