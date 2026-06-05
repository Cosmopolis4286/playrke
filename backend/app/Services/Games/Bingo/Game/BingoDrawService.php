<?php

namespace App\Services\Games\Bingo\Game;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Events\Games\Bingo\BingoGameEnded;
use App\Events\Games\Bingo\BingoGameStarted;
use App\Events\Games\Bingo\BingoNumberCalled;
use App\Events\Games\Bingo\BingoWinnerDeclared;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BingoDrawService
{
    private const TOTAL_CHIPS         = 75;
    private const CHIP_INTERVAL_SECONDS = 5;

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

        // 2. Resolver ronda activa, crear si no existe
        $round = BingoRound::where('room_id', (string) $roomId)
            ->whereIn('status', ['ACTIVE', 'IN_PROGRESS'])
            ->orderBy('round')
            ->first()
            ?? BingoRound::where('room_id', (string) $roomId)
                ->orderBy('round')
                ->first();

        if (!$round) {
            $config = $room->relationLoaded('config') ? $room->config : $room->load('config')->config;
            if (!$config) {
                throw new \DomainException('La sala no tiene configuración ni rondas.');
            }
            $defaultPattern = \App\Domains\Games\Bingo\Models\Pattern\BingoPattern::where('default_pattern', 'Y')->first()
                ?? \App\Domains\Games\Bingo\Models\Pattern\BingoPattern::first();
            if (!$defaultPattern) {
                throw new \DomainException('No hay patrones de bingo configurados.');
            }
            $round = BingoRound::create([
                'room_id'          => (string) $roomId,
                'room_config_id'   => (string) $config->id,
                'round'            => 1,
                'bingo_pattern_id' => (string) $defaultPattern->id,
                'prize_type'       => 'PCT',
                'status'           => 'ACTIVE',
            ]);
        }

        $roundId = (string) $round->id;

        // 3. Generar y persistir secuencia
        $sequence = $this->generateSequence();
        $this->persistDrawCodes($roomId, $sequence);

        // 4. Primera ficha + GameStarted
        $firstDraw = $this->createDrawRecord($roomId, $roundId, $sequence[0], 0);
        broadcast(new BingoGameStarted($roomId, (int) $firstDraw->id));
        broadcast(new BingoNumberCalled((int) $sequence[0], $roomId));

        $drawnSoFar = [$sequence[0]];

        if ($result = $this->detectWinnerAndEnd($room, $drawnSoFar, $roundId, $firstDraw->id)) {
            return $result;
        }

        // 5. Fichas restantes
        for ($i = 1, $total = count($sequence); $i < $total; $i++) {
            sleep(self::CHIP_INTERVAL_SECONDS);

            $chip = $sequence[$i];
            $draw = $this->createDrawRecord($roomId, $roundId, $chip, $i * self::CHIP_INTERVAL_SECONDS);

            broadcast(new BingoNumberCalled((int) $chip, $roomId));
            $drawnSoFar[] = $chip;

            Log::info('[BingoDrawService] Ficha', ['chip' => $chip, 'index' => $i]);

            if ($result = $this->detectWinnerAndEnd($room, $drawnSoFar, $roundId, $draw->id)) {
                return $result;
            }
        }

        // 6. Sorteo completo sin ganador
        $lastDraw = BingoDraw::where('room_id', (string) $roomId)->orderBy('created_at', 'desc')->first();

        broadcast(new BingoGameEnded(
            roomId:     (int) $roomId,
            drawId:     (int) ($lastDraw?->id ?? 0),
            hasWinner:  false,
            totalDrawn: count($drawnSoFar),
            winners:    [],
            prize:      (float) ($room->prize_pool ?? 0),
            endTime:    now()->toISOString(),
        ));

        Log::info('[BingoDrawService] Sorteo completado sin ganador', ['room_id' => $roomId, 'drawn' => count($drawnSoFar)]);

        return ['success' => true, 'room_id' => $roomId, 'total_chips' => count($drawnSoFar), 'winner' => null];
    }

    private function generateSequence(): array
    {
        $numbers = range(1, self::TOTAL_CHIPS);
        shuffle($numbers);
        return $numbers;
    }

    private function persistDrawCodes(string $roomId, array $sequence): void
    {
        foreach ($sequence as $index => $number) {
            BingoDrawCode::create([
                'room_id' => $roomId,
                'num'     => $index + 1,
                'sym'     => (string) $number,
            ]);
        }
    }

    private function createDrawRecord(string $roomId, string $roundId, int $chip, int $time): BingoDraw
    {
        return BingoDraw::create([
            'room_id'  => $roomId,
            'round_id' => $roundId,
            'chip'     => (string) $chip,
            'code'     => $this->generateBingoCode($chip),
            'time'     => $time,
            'status'   => 'FINISHED',
        ]);
    }

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

    /**
     * Verifica si algún cartón activo tiene bingo completo.
     * Si hay ganador(es): marca cartones, emite WinnerDeclared + GameEnded y retorna resumen.
     */
    private function detectWinnerAndEnd(BingoRoom $room, array $drawnNumbers, string $roundId, int $lastDrawId): ?array
    {
        $drawnSet = array_flip($drawnNumbers);

        $cards = BingoCard::where('room_id', (string) $room->id)
            ->where('status', BingoCard::STATUS_ACTIVE)
            ->with(['roomUser.user'])
            ->get();

        $winners = [];

        foreach ($cards as $card) {
            $matrix   = $card->card_numbers ?? [];
            $hasBingo = true;

            foreach (['B', 'I', 'N', 'G', 'O'] as $col) {
                foreach (($matrix[$col] ?? []) as $rowIndex => $num) {
                    if ($col === 'N' && $rowIndex === 2) continue; // celda libre
                    if ($num === null || !isset($drawnSet[$num])) {
                        $hasBingo = false;
                        break 2;
                    }
                }
            }

            if (!$hasBingo) continue;

            $card->update(['status' => BingoCard::STATUS_WINNER]);

            $userId   = $card->roomUser?->user_id ?? $card->creator_user_id;
            $user     = $card->roomUser?->user ?? User::find($userId);
            $userName = $user?->name ?? ('Jugador #' . $userId);
            $prize    = (float) ($room->prize_pool ?? 0);

            // Evento individual por ganador (para el jugador específico)
            broadcast(new BingoWinnerDeclared(
                roomId:     (int) $room->id,
                drawId:     $lastDrawId,
                winnerId:   (int) $userId,
                winnerName: $userName,
                cardId:     (int) $card->id,
                pattern:    'Cartón Lleno',
                prize:      $prize,
            ));

            $winners[] = [
                'user_id'     => (int) $userId,
                'user_name'   => $userName,
                'card_id'     => (string) $card->id,
                'card_serial' => $card->serial_number,
                'prize'       => $prize,
            ];
        }

        if (empty($winners)) {
            return null;
        }

        // Evento de fin de juego con resumen completo (para todos en la sala)
        broadcast(new BingoGameEnded(
            roomId:     (int) $room->id,
            drawId:     $lastDrawId,
            hasWinner:  true,
            totalDrawn: count($drawnNumbers),
            winners:    $winners,
            prize:      $winners[0]['prize'],
            endTime:    now()->toISOString(),
        ));

        Log::info('[BingoDrawService] Ganador(es) detectado(s) — sorteo detenido', [
            'room_id' => $room->id,
            'drawn'   => count($drawnNumbers),
            'winners' => count($winners),
        ]);

        return [
            'success'     => true,
            'room_id'     => $room->id,
            'total_chips' => count($drawnNumbers),
            'winners'     => $winners,
        ];
    }
}
