<?php

namespace App\Services\Games\Bingo\Rooms;

use App\Models\{BingoRoom, BingoCtns, BingoDraws};
use Illuminate\Support\Facades\Log;
use App\Enums\Games\Bingo\RoundStatus;
use App\Services\Games\Bingo\Cartons\PrizeCartonService;
use App\Services\Games\Bingo\Rounds\RoundsService;

class RoomClosureService
{
    private PrizeCartonService $prizeCartonService;
    private RoundsService $roundsService;

    public function __construct(RoundsService $roundsService, PrizeCartonService $prizeCartonService)
    {
        $this->roundsService = $roundsService;
        $this->prizeCartonService = $prizeCartonService;
    }

    /**
     * Procesa el cierre de una sala.
     *
     * @param \App\Models\BingoRoom $room
     */
    public function processClosure(BingoRoom $room): void
    {
        date_default_timezone_set(config('app.timezone'));

        if (time() < $room->Config->unix_date) {
            return; // La sala aún no debe cerrarse
        }

        $roomId = $room->id;
        $status = RoundStatus::DRAFT; // Por defecto

        if (BingoCtns::getCountCtnByRoom($roomId)) {
            $winChip = BingoDraws::getWinningChip($roomId);

            if (isset($winChip['Error'])) {
                Log::info($winChip['Error'] . ": Sala [ID => $roomId]");
                return;
            }

            // ✅ Definir estado según hora del chip ganador
            $status = ($winChip->time <= time())
                ? RoundStatus::FINISHED
                : RoundStatus::IN_PROGRESS;

            BingoRoom::where('id', $roomId)->update(['status' => $status->value]);

            $this->roundsService->determineRoundsStatus($roomId, $status);
            $this->prizeCartonService->registerCtnPrices($roomId);
        }

        BingoRoom::where('id', $roomId)->update(['status' => $status->value]);
    }
}
