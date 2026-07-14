<?php

namespace App\Domains\Games\Bingo\Jobs;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Game\BingoDrawService;
use App\Domains\Games\Bingo\Services\Game\BingoRoomHealthCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
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

    public function handle(BingoDrawService $drawService, BingoRoomHealthCheckService $healthCheck): void
    {
        // Lock atómico por sala: previene que dos instancias del job corran en paralelo
        // (puede ocurrir cuando el cron despacha mientras un retry del worker anterior
        // sigue en la cola, o si retry_after < duración real del sorteo).
        // TTL = 4 200 s (70 min) — mayor que el sorteo más largo posible (75 × 30 s = 2 250 s).
        $lock = Cache::lock("bingo:draw-lock:{$this->roomId}", 4200);

        if (!$lock->get()) {
            Log::info('[StartBingoDrawJob] Sorteo ya en curso (lock activo) — descartando instancia duplicada', [
                'room_id' => $this->roomId,
            ]);
            return;
        }

        try {
            $room = BingoRoom::with(['status', 'config', 'rounds'])->find($this->roomId);

            if (!$room) {
                Log::warning('[StartBingoDrawJob] Sala no encontrada', ['room_id' => $this->roomId]);
                return;
            }

            $errors = $healthCheck->collectErrors($room);

            if (!empty($errors)) {
                Log::warning('[StartBingoDrawJob] Sala no está lista para sorteo — omitiendo', [
                    'room_id' => $room->id,
                    'status'  => $room->status?->code,
                    'errors'  => $errors,
                ]);
                return;
            }

            // Si ya hay fichas cantadas pero la sala sigue activa, es un sorteo interrumpido
            // (p.ej. el worker se reinició a mitad del sorteo) — BingoDrawService::startDraw()
            // detecta este caso y retoma desde la última ficha persistida en vez de reiniciar.
            Log::info('[StartBingoDrawJob] Iniciando/retomando sorteo autónomo', ['room_id' => $room->id]);

            $drawService->startDraw($room);
        } finally {
            $lock->release();
        }
    }
}
