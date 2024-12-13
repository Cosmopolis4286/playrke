<?php

namespace App\Services\Game\Bingo;

use App\Models\{BingoCtns, BingoDraw, BingoPrizeCtns, BingoRoom, BingoRoomUser};
use App\Traits\BingoTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HandleBingoRooms
{
    use BingoTrait;

    public function checkRoomClosure($room = '')
    {
        $query = BingoRoom::whereIn('status', [2, 3]);

        if ($room) $query = $query->where('id', $room);

        $rooms = $query->get(['id', 'unix_date']);

        if (count($rooms)) {

            date_default_timezone_set(config('app.timezone'));

            foreach ($rooms as $room) {

                $roomID = $room->id;

                if (time() < $room->unix_date) continue;

                $existsCtns = BingoCtns::getCountCtnByRoom($roomID);
                $existsPlayers = BingoRoomUser::getPlayerCountByRoom($roomID);

                if ($existsCtns && $existsPlayers) {
                    $winChip = BingoDraw::getWinningChip($roomID) ?? $this->newDraw($roomID);

                    //dd($winChip->time <= time());

                    $status = ($winChip->time <= time()) ? 4 : 3;
                } else {
                    $status = 1; //N/D Borrador
                }

                BingoRoom::where('id', $roomID)->update(['status' => $status]);
            }
        }

        $this->handleFinishedRooms();
    }

    private function handleFinishedRooms()
    {
        $rooms = BingoRoom::where('status', 4)->get(['id', 'customer', 'date', 'time']);

        if (!count($rooms)) return true;

        date_default_timezone_set(config('app.timezone'));

        foreach ($rooms as $room) {

            $getWinnersJackpot = BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 1);

            if ($getWinnersJackpot == 0) {
                $this->handleJackpotWinners($room->id);
            }

            $dataTime = strtotime("{$room->date} {$room->time}");

            // Sumar 2 semanas (14 días * 24 horas * 60 minutos * 60 segundos)
            $dataTime2Week = $dataTime + (2 * 7 * 24 * 60 * 60);

            if ($dataTime2Week <= time()) { // Status (6) Sala Oculta
                BingoRoom::where('id', $room->id)->update(['status' => 6]);
            }
        }

        return true;
    }
}
