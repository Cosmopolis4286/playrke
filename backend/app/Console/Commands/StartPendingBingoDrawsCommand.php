<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Jobs\StartBingoDrawJob;
use App\Services\Commands\CommandService;
use App\Services\NotificationService;
use App\Traits\ResolvesSuperadminIds;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Detecta salas de bingo cuya hora de inicio ya llegó y que cuentan con al
 * menos un jugador registrado, y dispara su sorteo de forma autónoma —
 * sin depender de que un cliente tenga la página del juego abierta.
 *
 * Antes de este comando, el sorteo solo se iniciaba vía un fetch() lanzado
 * por el navegador cuando el countdown llegaba a cero (ver Index.vue), lo que
 * dejaba la sala congelada indefinidamente si nadie la visitaba a tiempo.
 */
class StartPendingBingoDrawsCommand extends Command
{
    use ResolvesSuperadminIds;

    protected $signature   = 'bingo:start-pending-draws';
    protected $description = 'Inicia automáticamente el sorteo de salas de bingo listas (hora cumplida + jugadores) sin depender de clientes conectados';

    /**
     * Ventana entre reintentos de recuperación de una misma sala atascada —
     * evita redespachar StartBingoDrawJob en cada corrida del minuto si el
     * primer reintento no resolvió el problema (ej. GameStatus FINISHED
     * faltante en config, algo que un segundo intento inmediato tampoco
     * arreglaría). Tras esta ventana, si sigue atascada, se notifica.
     */
    private const STUCK_ROOM_RETRY_WINDOW_MINUTES = 5;

    public function handle(): void
    {
        $status = 'SUCCESS';
        $started = 0;

        try {
            $now = now()->timestamp;

            // Todos los agregados necesarios se cargan en una sola query con withCount/withExists
            // para evitar N+1 (antes: 4 queries adicionales por sala dentro del foreach).
            $rooms = BingoRoom::query()
                ->whereHas('status', fn($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
                // Las salas sin horario (start_mode = MANUAL, unix_date null) quedan fuera:
                // su sorteo solo se dispara vía activación manual (BingoRoomCrudService::activateNow).
                ->whereHas('config', fn($q) => $q->whereNotNull('unix_date')->where('unix_date', '<=', $now))
                ->whereHas('players', fn($q) => $q->whereIn('role', [
                    BingoRoomUser::ROLE_PLAYER,
                    BingoRoomUser::ROLE_PLAYER_SELLER,
                ]))
                ->withCount([
                    'players as registered_players_count' => fn($q) => $q->whereIn('role', [
                        BingoRoomUser::ROLE_PLAYER,
                        BingoRoomUser::ROLE_PLAYER_SELLER,
                    ]),
                    'cards as total_cards_count',
                    // drawn_count y sequence_count se scopean al round activo/en-progreso
                    // para evitar que los draws de rondas anteriores (ya completadas)
                    // inflen el contador y salten el dispatch de una nueva ronda.
                    'draws as drawn_count'    => fn($q) => $q->whereHas('round', fn($r) => $r->whereIn('status', ['ACTIVE', 'IN_PROGRESS'])),
                    'drawCodes as sequence_count' => fn($q) => $q->whereHas('round', fn($r) => $r->whereIn('status', ['ACTIVE', 'IN_PROGRESS'])),
                    'rounds as active_round_count'   => fn($q) => $q->whereIn('status', ['ACTIVE', 'IN_PROGRESS']),
                    'rounds as finished_round_count' => fn($q) => $q->where('status', 'FINISHED'),
                ])
                ->with('config:id,room_id,unix_date,min_cards_to_start,min_players')
                ->get();

            foreach ($rooms as $room) {
                // Condición opcional: la sala exige una cantidad mínima de cartones
                // vendidos antes de poder iniciar su sorteo (ver min_cards_to_start).
                $minCards = $room->config?->min_cards_to_start;
                if ($minCards !== null && ($room->total_cards_count ?? 0) < $minCards) {
                    continue;
                }

                // Condición opcional análoga, pero de jugadores en vez de cartones —
                // reutiliza registered_players_count ya calculado en el withCount de
                // arriba, sin agregar una query nueva.
                $minPlayers = $room->config?->min_players;
                if ($minPlayers !== null && ($room->registered_players_count ?? 0) < $minPlayers) {
                    continue;
                }

                // Sorteo ya completo (todas las fichas de la secuencia fueron cantadas) pero
                // la sala sigue ACTIVE/IN_PROGRESS sin ronda FINISHED: closeRoomIfFinalRound()
                // probablemente falló la primera vez. BingoDrawService::runDraw() ya tiene una
                // ruta de recuperación para este estado exacto (rama $allDone, no re-declara
                // premio si ya existe) — redespachar el job es seguro y reusa esa ruta en vez
                // de duplicar lógica de cierre aquí.
                if ($room->sequence_count > 0 && $room->drawn_count >= $room->sequence_count) {
                    if ($room->active_round_count === 0 && $room->finished_round_count === 0) {
                        $this->recoverStuckRoom(
                            $room,
                            reason: 'sorteo completo pero sin rondas FINISHED ni cierre de sala',
                            canRetryViaJob: true,
                        );
                    }
                    continue;
                }

                // La sala ya tiene una ronda FINISHED sin ronda activa siguiente: el sorteo
                // ya se resolvió (con o sin ganador). Si sigue ACTIVE/IN_PROGRESS es porque
                // closeRoomIfFinalRound() no pudo cerrarla — no debe volver a sortearse cada
                // minuto (eso generaba un BingoCardPrize y un pago nuevos en cada corrida).
                // A diferencia del caso anterior, redespachar el job NO ayuda aquí:
                // BingoRoomHealthCheckService::assertNotAlreadyFinished() lo bloquearía
                // (finished_round_count >= totalRounds) — solo se alerta, sin auto-recuperación.
                if ($room->active_round_count === 0 && $room->finished_round_count > 0) {
                    $this->recoverStuckRoom(
                        $room,
                        reason: 'ronda FINISHED existente pero sala sigue ACTIVE/IN_PROGRESS sin cerrar',
                        canRetryViaJob: false,
                    );
                    continue;
                }

                // ShouldBeUnique en StartBingoDrawJob garantiza que el queue driver
                // rechace este dispatch si ya hay una instancia encolada o en ejecución
                // para esta sala — no se necesita lock manual aquí.
                StartBingoDrawJob::dispatch((string) $room->id)->onQueue('bingo-draw');
                $started++;

                $this->info("Sorteo despachado para sala #{$room->id} (jugadores: {$room->registered_players_count})");
            }

            $output = "Revisión completada. Sorteos despachados: {$started}.";
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            $this->error($output);
        }

        // logCommandExecution hace un INSERT — si la BD está caída (misma causa del
        // error capturado arriba), esta llamada también lanzaría, suprimiendo el output
        // original. Envolver en try/catch garantiza que el error visible al scheduler
        // sea el original, no un error de logging.
        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }

    /**
     * Sala detectada en estado atascado (ver los dos casos en handle()).
     * Primer intento: redespachar el job de sorteo si es seguro hacerlo
     * (canRetryViaJob) — no en cada corrida del minuto, sino respetando
     * STUCK_ROOM_RETRY_WINDOW_MINUTES para no inundar la cola si el
     * problema de fondo no se resuelve solo con un reintento. Si la
     * ventana ya se usó (o el caso no admite reintento vía job), notifica
     * a superadmins en vez de solo loguear silenciosamente cada minuto.
     */
    private function recoverStuckRoom(BingoRoom $room, string $reason, bool $canRetryViaJob): void
    {
        $cacheKey = "bingo:room:{$room->id}:stuck-retry";

        Log::critical("[StartPendingBingoDrawsCommand] Sala atascada: {$reason}", [
            'room_id'  => $room->id,
            'drawn'    => $room->drawn_count,
            'sequence' => $room->sequence_count,
        ]);

        if (Cache::has($cacheKey)) {
            // Ya se intentó recuperar/notificar recientemente — no repetir
            // en cada corrida del minuto mientras dure la ventana.
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(self::STUCK_ROOM_RETRY_WINDOW_MINUTES));

        if ($canRetryViaJob) {
            StartBingoDrawJob::dispatch((string) $room->id)->onQueue('bingo-draw');
            $this->warn("Sala #{$room->id} atascada — redespachado sorteo para recuperación automática.");
            return;
        }

        $this->notifyStuckRoom($room, $reason);
    }

    private function notifyStuckRoom(BingoRoom $room, string $reason): void
    {
        $notificationService = app(NotificationService::class);

        foreach ($this->superadminIds() as $superadminId) {
            $notificationService->create(
                userId: $superadminId,
                type: 'SYSTEM',
                title: '🚨 Sala de Bingo atascada',
                message: "La sala #{$room->id} quedó atascada ({$reason}) y requiere revisión manual.",
                data: [
                    'room_id' => (string) $room->id,
                    'reason'  => $reason,
                    'url'     => '/bingo/game-rooms',
                ],
            );
        }
    }
}
