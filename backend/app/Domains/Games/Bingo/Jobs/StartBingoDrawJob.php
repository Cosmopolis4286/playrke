<?php

namespace App\Domains\Games\Bingo\Jobs;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Game\BingoDrawService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Inicia el sorteo de una sala de bingo de forma autónoma (sin depender de
 * que un cliente tenga la página del juego abierta).
 *
 * Se despacha desde StartPendingBingoDrawsCommand cuando una sala llega a su
 * hora de inicio con al menos un jugador registrado.
 *
 * El método startDraw() bloquea durante todo el sorteo (~75 fichas × 5s),
 * por eso se ejecuta en la cola dedicada 'bingo-draw' y no inline.
 */
final class StartBingoDrawJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Sin límite de tiempo — el sorteo completo puede tardar varios minutos. */
    public int $timeout = 0;

    public function __construct(private readonly string $roomId) {}

    public function handle(BingoDrawService $drawService): void
    {
        $room = BingoRoom::with(['status', 'config'])->find($this->roomId);

        if (!$room) {
            Log::warning('[StartBingoDrawJob] Sala no encontrada', ['room_id' => $this->roomId]);
            return;
        }

        if (!in_array($room->status?->code, ['ACTIVE', 'IN_PROGRESS'], true)) {
            Log::info('[StartBingoDrawJob] Sala ya no está en estado válido para sorteo', [
                'room_id' => $room->id,
                'status'  => $room->status?->code,
            ]);
            return;
        }

        // Si ya hay fichas cantadas pero la sala sigue activa, es un sorteo interrumpido
        // (p.ej. el worker se reinició a mitad del sorteo) — BingoDrawService::startDraw()
        // detecta este caso y retoma desde la última ficha persistida en vez de reiniciar.
        // Solo omitimos si la sala ya no está en un estado válido (otro proceso la cerró).
        Log::info('[StartBingoDrawJob] Iniciando/retomando sorteo autónomo', ['room_id' => $room->id]);

        $drawService->startDraw($room);
    }
}
