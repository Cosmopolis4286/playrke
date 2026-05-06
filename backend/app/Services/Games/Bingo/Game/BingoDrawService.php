<?php

namespace App\Services\Games\Bingo\Game;

use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Events\Games\Bingo\BingoGameStarted;
use App\Events\Games\Bingo\BingoNumberCalled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BingoDrawService
{
    /**
     * Total de fichas en bingo (1–75).
     */
    private const TOTAL_CHIPS = 75;

    /**
     * Intervalo entre fichas en segundos (usado en entorno local/dev).
     */
    private const CHIP_INTERVAL_SECONDS = 5;

    /**
     * Inicia el sorteo para una sala:
     *  1. Limpia sorteos previos (útil para re-inicios en dev).
     *  2. Genera secuencia aleatoria 1–75.
     *  3. Persiste la secuencia en bingo_draws_code.
     *  4. Dispara BingoGameStarted.
     *  5. Por cada ficha: crea registro en bingo_draws + dispara BingoNumberCalled.
     *     (con sleep entre fichas en dev para simular tempo real)
     *
     * ⚠️  Este método bloquea el proceso durante ~375 s (75 fichas × 5 s).
     *     En producción deberá moverse a un Job/Queue.
     *
     * @param  BingoRoom $room
     * @return array     Resumen del sorteo
     */
    public function startDraw(BingoRoom $room): array
    {
        @set_time_limit(0);
        ignore_user_abort(true);

        $roomId = $room->id;

        Log::info('[BingoDrawService] Iniciando sorteo', ['room_id' => $roomId]);

        // 1. Limpiar sorteos previos
        DB::transaction(function () use ($roomId) {
            BingoDraw::where('room_id', $roomId)->delete();
            BingoDrawCode::where('room_id', $roomId)->delete();
        });

        // 2. Generar secuencia aleatoria
        $sequence = $this->generateSequence();

        // 3. Persistir secuencia completa en bingo_draws_code
        $this->persistDrawCodes($roomId, $sequence);

        // 4. Crear primer registro de sorteo para obtener draw_id
        $firstDraw = $this->createDrawRecord($roomId, 1, $sequence[0], 0);

        // 5. Broadcast GameStarted
        broadcast(new BingoGameStarted($roomId, (int) $firstDraw->id));

        Log::info('[BingoDrawService] Evento GameStarted disparado', [
            'room_id' => $roomId,
            'draw_id' => $firstDraw->id,
        ]);

        // 6. Broadcast primera ficha
        broadcast(new BingoNumberCalled((int) $sequence[0], $roomId));

        // 7. Registrar y difundir fichas restantes con intervalo
        for ($i = 1, $total = count($sequence); $i < $total; $i++) {
            sleep(self::CHIP_INTERVAL_SECONDS);

            $chip = $sequence[$i];
            $time = $i * self::CHIP_INTERVAL_SECONDS;

            $this->createDrawRecord($roomId, 1, $chip, $time);

            broadcast(new BingoNumberCalled((int) $chip, $roomId));

            Log::info('[BingoDrawService] Ficha difundida', [
                'room_id' => $roomId,
                'chip'    => $chip,
                'index'   => $i,
            ]);
        }

        Log::info('[BingoDrawService] Sorteo completado', ['room_id' => $roomId]);

        return [
            'success'     => true,
            'room_id'     => $roomId,
            'total_chips' => count($sequence),
            'sequence'    => $sequence,
        ];
    }

    /**
     * Genera y retorna la secuencia aleatoria de fichas (1–75).
     *
     * @return int[]
     */
    private function generateSequence(): array
    {
        $numbers = range(1, self::TOTAL_CHIPS);
        shuffle($numbers);
        return $numbers;
    }

    /**
     * Persiste la secuencia completa en bingo_draws_code.
     *
     * - num = posición en la secuencia (1-indexed, máximo 75 → cabe en tinyInt)
     * - sym = el número de la ficha como string (ej: "34", "B-12" no, solo el número)
     *
     * @param string $roomId
     * @param int[]  $sequence
     */
    private function persistDrawCodes(string $roomId, array $sequence): void
    {
        foreach ($sequence as $index => $number) {
            BingoDrawCode::create([
                'room_id' => $roomId,
                'num'     => $index + 1,          // posición 1-indexed
                'sym'     => (string) $number,     // número de la ficha
            ]);
        }
    }

    /**
     * Crea un registro en bingo_draws para una ficha.
     *
     * @param string $roomId
     * @param int    $round
     * @param int    $chip    Número de ficha (1–75)
     * @param int    $time    Segundos desde inicio del sorteo
     * @return BingoDraw
     */
    private function createDrawRecord(string $roomId, int $round, int $chip, int $time): BingoDraw
    {
        return BingoDraw::create([
            'room_id' => $roomId,
            'round'   => (string) $round,
            'chip'    => (string) $chip,
            'code'    => $this->generateBingoCode($chip),
            'time'    => $time,
            'status'  => 'FINISHED',
        ]);
    }

    /**
     * Genera código de bingo estándar (BINGO, letras por rango).
     *
     * B = 1-15, I = 16-30, N = 31-45, G = 46-60, O = 61-75
     *
     * @param  int    $chip
     * @return string Ej: "B-7", "N-42"
     */
    private function generateBingoCode(int $chip): string
    {
        $letter = match (true) {
            $chip <= 15 => 'B',
            $chip <= 30 => 'I',
            $chip <= 45 => 'N',
            $chip <= 60 => 'G',
            default     => 'O',
        };

        return "{$letter}-{$chip}";
    }
}
