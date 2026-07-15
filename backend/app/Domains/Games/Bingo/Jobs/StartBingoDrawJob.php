<?php

namespace App\Domains\Games\Bingo\Jobs;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Game\BingoDrawService;
use App\Domains\Games\Bingo\Services\Game\BingoRoomHealthCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
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
 *
 * ShouldBeUniqueUntilProcessing garantiza que solo exista UNA instancia encolada
 * por sala. El lock se libera cuando el job empieza a ejecutarse — no al final —
 * de modo que si el worker muere durante el sorteo, el cron puede re-despachar el
 * job y BingoDrawService::startDraw() lo retomará desde la última ficha persistida.
 * Con ShouldBeUnique (sin UntilProcessing), un crash de worker dejaría el lock
 * por uniqueFor segundos (75 min) y el draw quedaría congelado sin posibilidad de
 * retoma automática.
 */
final class StartBingoDrawJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Sin límite de tiempo — el sorteo completo puede tardar varios minutos. */
    public int $timeout = 0;

    /**
     * Máximo de intentos antes de pasar el job a failed_jobs.
     * 3 reintentos cubre crashes transitorios del worker sin generar un
     * loop infinito cuando hay un error permanente (ej. migración pendiente).
     */
    public int $tries = 3;

    /** Segundos entre reintentos — da tiempo al sistema a recuperarse. */
    public int $backoff = 30;

    /**
     * Ventana de unicidad: cubre el tiempo máximo que el job puede pasar en cola
     * esperando a ser procesado. Con ShouldBeUniqueUntilProcessing el lock se libera
     * al inicio del handle(), así que este valor solo necesita cubrir el queue wait,
     * no el sorteo completo. 3 600 s (1 hora) es un margen holgado.
     */
    public int $uniqueFor = 3600;

    public function __construct(private readonly string $roomId) {}

    /** Clave única por sala — impide encolar dos sorteos para la misma sala. */
    public function uniqueId(): string
    {
        return $this->roomId;
    }

    public function handle(BingoDrawService $drawService, BingoRoomHealthCheckService $healthCheck): void
    {
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
    }
}
