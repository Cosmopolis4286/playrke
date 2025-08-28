<?php

namespace App\Services\Games\Bingo\Rounds;

use App\Models\{BingoRounds, BingoRoundsDrafts};
use App\Enums\Games\Bingo\RoundStatus;
use Illuminate\Support\Facades\Log;

class RoundsService
{
    /**
     * Asocia las rondas a la sala.
     *
     * @param int $automaticBingoID
     * @param int $roomId
     * @return void
     */
    public function associateRounds(int $automaticBingoID, int $roomId): void
    {
        $draft = BingoRoundsDrafts::getByAutomaticRoom($automaticBingoID);

        if (!$draft) {
            Log::warning("No hay draft de rondas para automatic bingo ID {$automaticBingoID}");
            return;
        }

        BingoRounds::create([
            'room'         => $roomId,
            'rounds'       => $draft->rounds,
            'prize_types'  => $draft->prize_types,
            'play_types'   => $draft->play_types,
            'prize_values' => $draft->prize_values,
            'status'       => RoundStatus::ACTIVE->value, // ✅ Guardamos el valor del enum
        ]);

        $draft->update(['status' => RoundStatus::FINISHED->value]); // ✅ Enum en draft
    }

    /**
     * Determina y actualiza el estado de las rondas de una sala.
     *
     * @param int $roomId        ID de la sala.
     * @param RoundStatus $status Estado a asignar (enum).
     * @return void
     */
    public function determineRoundsStatus(int $roomId, RoundStatus $status): void
    {
        try {
            BingoRounds::where('room', $roomId)->update([
                'status' => $status->value, // ✅ Usamos el valor del enum (string)
            ]);

            Log::info("Estado de rondas actualizado para sala [ID => {$roomId}] a [{$status->value}].");
        } catch (\Throwable $e) {
            Log::error("Error al actualizar estado de rondas para sala [ID => {$roomId}]: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
