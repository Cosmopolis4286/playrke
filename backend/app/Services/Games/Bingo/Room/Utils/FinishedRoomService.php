<?php

namespace App\Services\Games\Bingo\Room\Utils;

use App\Models\{BingoRoom, BingoPrizeCtns, User};
use App\Services\Games\Bingo\Round\RoundsService;
use App\Services\Games\Bingo\Card\Customer\CustomerCartonService;
use Illuminate\Support\Facades\Log;
use App\Enums\Games\Bingo\RoundStatus;
use App\Enums\Games\GameStatus;

class FinishedRoomService
{
    private RoundsService $roundsService;
    private CustomerCartonService $customerCartonService;
    private ?BingoWinnerService $winnerService;

    /**
     * Inyecta dependencias necesarias para procesar salas finalizadas.
     */
    public function __construct(
        RoundsService $roundsService,
        CustomerCartonService $customerCartonService,
        ?BingoWinnerService $winnerService = null
    ) {
        $this->roundsService = $roundsService;
        $this->customerCartonService = $customerCartonService;
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
            // ✅ Determinar estado de rondas como FINALIZADO (usando enum)
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
     *
     * @param int $roomId
     */
    private function processWinners(int $roomId): void
    {
        if (!$this->winnerService) {
            Log::warning("BingoWinnerService no disponible para procesar ganadores de sala [ID => {$roomId}]");
            return;
        }

        if (!BingoPrizeCtns::getWinnersCountByPrizeType($roomId, 'Cartón Lleno')) {
            $this->winnerService->handleWinners($roomId);
        } else {
            $this->winnerService->markAllWinCtnAsPaid($roomId);
        }
    }

    /**
     * Procesa los premios según el tipo de creador.
     *
     * @param BingoRoom $room
     */
    private function processCreator(BingoRoom $room): void
    {
        $creator = User::getCreatorUser($room->creator_user, false, true);

        match ($creator['type']) {
            'commerce_id' => BingoPrizeCtns::where('room', $room->id)->update(['active' => 'Y']),
            'customer' => $this->customerCartonService->handleCustomerPrizeCarton($room->creator_user),
            default    => Log::warning("Tipo de creador desconocido en sala [ID => {$room->id}]"),
        };
    }

    /**
     * Oculta la sala si ya pasaron 2 semanas desde su fecha.
     *
     * @param BingoRoom $room
     */
    private function checkAndHideRoom(BingoRoom $room): void
    {
        date_default_timezone_set(config('app.timezone'));

        $dataTime2Week = strtotime("{$room->Config->date} {$room->Config->time}") + (2 * 7 * 24 * 60 * 60);

        if ($dataTime2Week <= time()) {
            BingoRoom::where('id', $room->id)->update([
                'status' => GameStatus::FINISHED->value, // ✅ guardamos valor string del enum
            ]);
        }
    }
}
