<?php

namespace App\Services\Games\Bingo\Winner;

use App\Models\BingoPrizeCtns;
use Illuminate\Support\Facades\Log;

final class BingoWinnerService
{
    /**
     * Maneja los ganadores de una sala.
     *
     * @param int $roomId
     * @return void
     */
    public function handleWinners(int $roomId): void
    {
        // TODO: Implementar lógica real de asignación de premios
        Log::info("Manejando ganadores para la sala [ID => {$roomId}].");
    }

    /**
     * Marca todos los cartones ganadores como pagados.
     *
     * @param int $roomId
     * @return void
     */
    public function markAllWinCtnAsPaid(int $roomId): void
    {
        // TODO: Implementar lógica real de pago de premios
        BingoPrizeCtns::where('room', $roomId)->update(['paid' => true]);

        Log::info("Todos los cartones ganadores pagados para la sala [ID => {$roomId}].");
    }
}
