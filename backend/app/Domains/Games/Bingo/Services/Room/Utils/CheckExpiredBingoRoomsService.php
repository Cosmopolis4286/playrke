<?php

namespace App\Domains\Games\Bingo\Services\Room\Utils;

use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomStateCoordinator;
use App\Models\Games\Common\Utils\GameStatus;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Verifica salas de bingo cuya fecha+hora programada ya expiró (unix_date <= ahora)
 * y actualiza su estado según si tienen jugadores o no:
 *   - total_players == 0  → INACTIVE
 *   - total_players  > 0  → IN_PROGRESS
 *
 * Adicionalmente cancela salas que no alcanzaron el mínimo de cartones
 * (min_cards_to_start) o de jugadores (min_players) al momento de expirar,
 * iniciando el reembolso automático. Antes solo se miraba el de cartones: una
 * sala sin el mínimo de jugadores pasaba a IN_PROGRESS pero
 * StartPendingBingoDrawsCommand nunca la sorteaba (la salta por min_players),
 * quedando trabada para siempre con el dinero de los cartones retenido.
 */
final class CheckExpiredBingoRoomsService
{
    public function __construct(
        private readonly BingoRoomAuditLogger $auditLogger,
        private readonly BingoRoomStateCoordinator $roomState,
    ) {
    }

    public function handle() : void
    {
        $now = Carbon::now('UTC')->timestamp;

        $statusMap = GameStatus::codesMap(['INACTIVE', 'IN_PROGRESS', 'ACTIVE', 'CANCELLED']);
        $inactiveId = $statusMap['INACTIVE'] ?? null;
        $inProgressId = $statusMap['IN_PROGRESS'] ?? null;
        $activeId = $statusMap['ACTIVE'] ?? null;
        $cancelledId = $statusMap['CANCELLED'] ?? null;

        if (! $inactiveId || ! $inProgressId || ! $activeId || ! $cancelledId) {
            return;
        }

        $expiredSubquery = fn ($q) => $q->where('unix_date', '<=', $now);

        // Normalizar los contadores desde lotes/participaciones persistidos antes
        // de que la expiración tome decisiones de estado o reembolso.
        $expiredRoomIds = BingoRoom::where('status_id', $activeId)
            ->whereHas('config', $expiredSubquery)
            ->pluck('id');
        foreach ($expiredRoomIds as $expiredRoomId) {
            $this->roomState->synchronizeCounters((string) $expiredRoomId);
        }

        // 1. Salas con jugadores pero sin mínimo de cartones o de jugadores → CANCELLED + reembolso
        $this->cancelUnderMinimumRooms($activeId, $cancelledId, $expiredSubquery);

        // 2. Revalidar cada sala bajo lock con las mismas reglas de conteo.
        // No usar SQL contra total_players ni SUM(card_count): son snapshots y
        // pueden divergir de lotes/participaciones tras reembolsos o ventas POS.
        foreach ($expiredRoomIds as $expiredRoomId) {
            DB::transaction(function() use ($expiredRoomId, $activeId, $inProgressId, $inactiveId, $now) : void {
                $locked = BingoRoom::query()
                    ->whereKey((string) $expiredRoomId)
                    ->where('status_id', $activeId)
                    ->with('config')
                    ->lockForUpdate()
                    ->first();

                if (! $locked || ! $locked->config || (int) $locked->config->unix_date > $now) {
                    return;
                }

                $counts = $this->roomState->synchronizeCounters((string) $locked->id);
                $locked->forceFill($counts);

                if ($counts['total_players'] <= 0) {
                    $locked->update(['status_id' => $inactiveId]);

                    return;
                }

                // Si sigue faltando algún mínimo, el flujo de cancelación ya lo
                // dejó CANCELLED; no iniciar una sala que no puede sortearse.
                if ($this->unmetMinimum($locked) !== null) {
                    return;
                }

                $locked->update(['status_id' => $inProgressId]);
            }, 3);
        }
    }

    /**
     * Cancela salas ACTIVE expiradas que tienen jugadores pero no alcanzaron
     * el mínimo de cartones o de jugadores para iniciar el sorteo. Despacha reembolsos.
     */
    private function cancelUnderMinimumRooms(string $activeId, string $cancelledId, Closure $expiredSubquery) : void
    {
        // Salas con total_players > 0 Y algún mínimo configurado sin alcanzar.
        // Eager load 'config' para evitar N+1 en el filter().
        $underMinimum = BingoRoom::where('status_id', $activeId)
            ->whereHas('config', $expiredSubquery)
            ->whereHas('config', fn ($q) => $q->where(fn ($w) => $w->whereNotNull('min_cards_to_start')->orWhereNotNull('min_players')))
            ->with('config')
            ->get()
            ->filter(fn (BingoRoom $room) : bool => $this->unmetMinimum($room) !== null);

        foreach ($underMinimum as $room) {
            // lockForUpdate dentro de la transacción previene que dos ejecuciones
            // concurrentes del cron cancelen la misma sala dos veces.
            $reason = DB::transaction(function() use ($room, $cancelledId, $activeId) : ?string {
                $locked = BingoRoom::where('id', $room->id)
                    ->where('status_id', $activeId)
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return null; // Otro proceso ya cambió el estado
                }

                // La lista inicial solo descubre candidatas. Entre esa lectura y
                // este lock pudo completarse una venta/registro o cambiar la fecha.
                // Revalidar con datos actuales evita cancelar una sala que ya cumple
                // los mínimos y serializa la decisión con los guards de capacidad.
                $locked->load('config');
                if (! $locked->config || (int) $locked->config->unix_date > Carbon::now('UTC')->timestamp) {
                    return null;
                }

                $unmetMinimum = $this->unmetMinimum($locked);
                if ($unmetMinimum === null) {
                    return null;
                }

                $locked->update(['status_id' => $cancelledId]);

                return $unmetMinimum;
            });

            if ($reason === null) {
                continue;
            }

            $room = $room->fresh(['config']);

            Log::info('[CheckExpiredBingoRoomsService] Sala cancelada por mínimo no alcanzado', [
                'room_id' => $room->id,
                'reason' => $reason,
                'total_cards' => $room->total_cards ?? 0,
                'min_cards' => $room->config?->min_cards_to_start,
                'total_players' => $room->total_players,
                'min_players' => $room->config?->min_players,
            ]);

            $this->auditLogger->log(
                $room,
                BingoRoomAuditLogger::EVENT_CANCELLED,
                'system',
                "Sala cancelada automáticamente: mínimo de {$reason} no alcanzado al expirar",
                [
                    'total_cards' => $room->total_cards ?? 0,
                    'min_cards' => $room->config?->min_cards_to_start,
                    'total_players' => $room->total_players ?? 0,
                    'min_players' => $room->config?->min_players,
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

    /**
     * Qué mínimo configurado no se alcanzó: 'cards', 'players' o null si ambos
     * se cumplen (o no aplican). Cartones primero, por compatibilidad con el
     * motivo que ya se auditaba antes.
     */
    private function unmetMinimum(BingoRoom $room) : ?string
    {
        $counts = $this->roomState->synchronizeCounters((string) $room->id);
        $room->forceFill($counts);

        // Una sala vacía debe pasar a INACTIVE, no iniciar un reembolso vacío.
        if ($counts['total_players'] <= 0) {
            return null;
        }

        $minCards = $room->config?->min_cards_to_start;
        if ($minCards !== null) {
            // El contador denormalizado de la sala puede estar desfasado; para
            // una decisión financiera de cancelación, contar los lotes vigentes.
            if ($counts['total_cards'] < (int) $minCards) {
                return 'cards';
            }
        }

        $minPlayers = $room->config?->min_players;
        if ($minPlayers !== null && $counts['total_players'] < $minPlayers) {
            return 'players';
        }

        return null;
    }
}
