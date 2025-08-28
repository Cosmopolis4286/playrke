<?php

namespace App\Services\Games\Bingo\Rooms;

use App\Models\{BingoRoom, BingoPrizeCtns, User};
use App\Services\Games\Bingo\Rounds\RoundsService;
use App\Services\Games\Bingo\Cartons\PrizeCartonService;
use Illuminate\Support\Facades\Log;
use App\Enums\Games\Bingo\RoundStatus;

class FinishedRoomService
{
    private RoundsService $roundsService;
    private PrizeCartonService $prizeCartonService;
    private WinnerService $winnerService;

    /**
     * Inyecta dependencias necesarias para procesar salas finalizadas.
     */
    public function __construct(
        RoundsService $roundsService,
        PrizeCartonService $prizeCartonService,
        WinnerService $winnerService
    ) {
        $this->roundsService = $roundsService;
        $this->prizeCartonService = $prizeCartonService;
        $this->winnerService = $winnerService;
    }

    /**
     * Procesa una sala terminada.
     *
     * @param \App\Models\BingoRoom $room
     */
    public function processFinishedRoom(BingoRoom $room): void
    {
        try {
            // ✅ Determinar estado de rondas como FINALIZADO
            $this->roundsService->determineRoundsStatus($room->id, RoundStatus::FINISHED);

            // ✅ Procesar ganadores
            $this->processWinners($room->id);

            // ✅ Procesar pagos y premios según tipo de creador
            $this->processCreator($room);

            // ✅ Ocultar sala si aplica
            $this->checkAndHideRoom($room);
        } catch (\Throwable $e) {
            Log::error("Error al procesar sala finalizada [ID => {$room->id}]: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Procesa ganadores de una sala.
     */
    private function processWinners(int $roomId): void
    {
        if (!BingoPrizeCtns::getWinnersCountByPrizeType($roomId, 'Cartón Lleno')) {
            $this->winnerService->handleWinners($roomId);
        } else {
            $this->winnerService->markAllWinCtnAsPaid($roomId);
        }
    }

    /**
     * Procesa los premios según el tipo de creador.
     */
    private function processCreator(BingoRoom $room): void
    {
        $creator = User::getCreatorUser($room->creator_user, false, true);

        match ($creator['type']) {
            'commerce' => BingoPrizeCtns::where('room', $room->id)->update(['active' => 'Y']),
            'customer' => $this->prizeCartonService->handleCustomerPrizeCarton($room->creator_user),
        };
    }

    /**
     * Oculta la sala si ya pasaron 2 semanas desde su fecha.
     */
    private function checkAndHideRoom(BingoRoom $room): void
    {
        date_default_timezone_set(config('app.timezone'));

        $dataTime2Week = strtotime("{$room->Config->date} {$room->Config->time}") + (2 * 7 * 24 * 60 * 60);

        if ($dataTime2Week <= time()) {
            BingoRoom::where('id', $room->id)->update(['status' => RoundStatus::CLOSED->value]);
        }
    }
}
