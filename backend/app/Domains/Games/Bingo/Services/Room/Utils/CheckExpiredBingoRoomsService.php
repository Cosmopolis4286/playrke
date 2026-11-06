<?php

namespace App\Domains\Games\Bingo\Services\Room\Utils;

use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
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

        // 1. Salas con jugadores pero sin mínimo de cartones o de jugadores → CANCELLED + reembolso
        $this->cancelUnderMinimumRooms($activeId, $cancelledId, $expiredSubquery);

        // 2. Salas con jugadores (y ambos mínimos alcanzados) → IN_PROGRESS
        // 3. Salas sin jugadores → INACTIVE
        DB::transaction(function() use ($activeId, $inProgressId, $inactiveId, $expiredSubquery) : void {
            BingoRoom::where('status_id', $activeId)
                ->whereHas('config', $expiredSubquery)
                ->where('total_players', '>', 0)
                ->where(function($q) {
                    // Solo si el mínimo de cartones está satisfecho (o no aplica)
                    $q->whereHas('config', fn ($c) => $c->whereNull('min_cards_to_start'))
                        ->orWhereHas('config', fn ($c) => $c->whereColumn('min_cards_to_start', '<=', DB::raw('(SELECT COALESCE(SUM(card_count),0) FROM bingo_card_lots WHERE room_id = bingo_rooms.id AND status != \'REFUNDED\')')));
                })
                ->where(function($q) {
                    // Y el mínimo de jugadores también (o no aplica)
                    $q->whereHas('config', fn ($c) => $c->whereNull('min_players'))
                        ->orWhereHas('config', fn ($c) => $c->whereColumn('min_players', '<=', 'bingo_rooms.total_players'));
                })
                ->update(['status_id' => $inProgressId]);

            BingoRoom::where('status_id', $activeId)
                ->whereHas('config', $expiredSubquery)
                ->where(fn ($q) => $q->where('total_players', 0)->orWhereNull('total_players'))
                ->update(['status_id' => $inactiveId]);
        });
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
            ->where('total_players', '>', 0)
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
        $minCards = $room->config?->min_cards_to_start;
        if ($minCards !== null) {
            // El contador denormalizado de la sala puede estar desfasado; para
            // una decisión financiera de cancelación, contar los lotes vigentes.
            $totalCards = BingoCardLot::where('room_id', $room->id)
                ->where('status', '!=', 'REFUNDED')
                ->sum('card_count');

            if ((int) $totalCards < (int) $minCards) {
                return 'cards';
            }
        }

        $minPlayers = $room->config?->min_players;
        if ($minPlayers !== null && ($room->total_players ?? 0) < $minPlayers) {
            return 'players';
        }

        return null;
    }
}
