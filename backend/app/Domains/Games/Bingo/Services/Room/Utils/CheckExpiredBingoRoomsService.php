<?php

namespace App\Domains\Games\Bingo\Services\Room\Utils;

use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Models\Games\Common\Utils\GameStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Verifica salas de bingo cuya fecha+hora programada ya expiró (unix_date <= ahora)
 * y actualiza su estado según si tienen jugadores o no:
 *   - total_players == 0  → INACTIVE
 *   - total_players  > 0  → IN_PROGRESS
 *
 * Adicionalmente cancela salas que no alcanzaron el mínimo de cartones requerido
 * (min_cards_to_start) al momento de expirar, iniciando el reembolso automático.
 */
final class CheckExpiredBingoRoomsService
{
    public function __construct(
        private readonly BingoRoomAuditLogger $auditLogger,
    ) {}

    public function handle(): void
    {
        $now = Carbon::now('UTC')->timestamp;

        $statusMap    = GameStatus::codesMap(['INACTIVE', 'IN_PROGRESS', 'ACTIVE', 'CANCELLED']);
        $inactiveId   = $statusMap['INACTIVE']    ?? null;
        $inProgressId = $statusMap['IN_PROGRESS'] ?? null;
        $activeId     = $statusMap['ACTIVE']      ?? null;
        $cancelledId  = $statusMap['CANCELLED']   ?? null;

        if (!$inactiveId || !$inProgressId || !$activeId || !$cancelledId) {
            return;
        }

        $expiredSubquery = fn($q) => $q->where('unix_date', '<=', $now);

        // 1. Salas con jugadores pero sin mínimo de cartones → CANCELLED + reembolso
        $this->cancelUnderMinimumRooms($activeId, $cancelledId, $expiredSubquery);

        // 2. Salas con jugadores (y mínimo alcanzado) → IN_PROGRESS
        // 3. Salas sin jugadores → INACTIVE
        DB::transaction(function () use ($activeId, $inProgressId, $inactiveId, $expiredSubquery): void {
            BingoRoom::where('status_id', $activeId)
                ->whereHas('config', $expiredSubquery)
                ->where('total_players', '>', 0)
                ->where(function ($q) {
                    // Solo si el mínimo de cartones está satisfecho (o no aplica)
                    $q->whereHas('config', fn($c) => $c->whereNull('min_cards_to_start'))
                      ->orWhereHas('config', fn($c) => $c->whereColumn('min_cards_to_start', '<=', DB::raw('(SELECT COALESCE(SUM(card_count),0) FROM bingo_card_lots WHERE room_id = bingo_rooms.id AND status != \'REFUNDED\')')));
                })
                ->update(['status_id' => $inProgressId]);

            BingoRoom::where('status_id', $activeId)
                ->whereHas('config', $expiredSubquery)
                ->where(fn($q) => $q->where('total_players', 0)->orWhereNull('total_players'))
                ->update(['status_id' => $inactiveId]);
        });
    }

    /**
     * Cancela salas ACTIVE expiradas que tienen jugadores pero no alcanzaron
     * el mínimo de cartones para iniciar el sorteo. Despacha reembolsos.
     */
    private function cancelUnderMinimumRooms(string $activeId, string $cancelledId, \Closure $expiredSubquery): void
    {
        // Salas con total_players > 0 Y min_cards_to_start configurado Y total_cards < min.
        // Eager load 'config' para evitar N+1 en el filter().
        $underMinimum = BingoRoom::where('status_id', $activeId)
            ->whereHas('config', $expiredSubquery)
            ->where('total_players', '>', 0)
            ->whereHas('config', fn($q) => $q->whereNotNull('min_cards_to_start'))
            ->with('config')
            ->get()
            ->filter(function (BingoRoom $room): bool {
                $minCards = $room->config?->min_cards_to_start;
                return $minCards !== null && ($room->total_cards ?? 0) < $minCards;
            });

        foreach ($underMinimum as $room) {
            // lockForUpdate dentro de la transacción previene que dos ejecuciones
            // concurrentes del cron cancelen la misma sala dos veces.
            $changed = DB::transaction(function () use ($room, $cancelledId, $activeId): bool {
                $locked = BingoRoom::where('id', $room->id)
                    ->where('status_id', $activeId)
                    ->lockForUpdate()
                    ->first();

                if (!$locked) {
                    return false; // Otro proceso ya cambió el estado
                }

                return (bool) $locked->update(['status_id' => $cancelledId]);
            });

            if (!$changed) {
                continue;
            }

            Log::info('[CheckExpiredBingoRoomsService] Sala cancelada por mínimo no alcanzado', [
                'room_id'          => $room->id,
                'total_cards'      => $room->total_cards ?? 0,
                'min_cards'        => $room->config?->min_cards_to_start,
                'total_players'    => $room->total_players,
            ]);

            $this->auditLogger->log(
                $room,
                BingoRoomAuditLogger::EVENT_CANCELLED,
                'system',
                'Sala cancelada automáticamente: mínimo de cartones no alcanzado al expirar',
                [
                    'total_cards' => $room->total_cards ?? 0,
                    'min_cards'   => $room->config?->min_cards_to_start,
                ],
            );

            $this->auditLogger->log(
                $room,
                BingoRoomAuditLogger::EVENT_REFUND_INITIATED,
                'system',
                'Reembolso automático iniciado por cancelación por mínimo no alcanzado',
                ['total_players' => $room->total_players ?? 0],
            );

            // pluck() en lugar de cursor() — evita "Commands out of sync" en MySQL
            // cuando dispatch() escribe jobs en la misma conexión PDO que cursor() mantiene abierta.
            $lotIds = \App\Domains\Games\Bingo\Models\Card\BingoCardLot::where('room_id', $room->id)
                ->where('status', 'ACTIVE')
                ->pluck('id');

            foreach ($lotIds as $lotId) {
                BingoCardLotRefundJob::dispatch((string) $lotId, (string) $room->id)
                    ->onQueue('bingo-refunds');
            }
        }
    }
}
