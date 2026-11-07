<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Room\Metrics;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Roles\Player\External\ExternalPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recalcula los agregados denormalizados de bingo_rooms desde sus registros
 * fuente. Los deltas son útiles para el camino rápido, pero no son la fuente
 * de verdad: reintentos, reembolsos, compras concurrentes y ventas POS pueden
 * dejar el snapshot desfasado.
 */
class BingoRoomMetricsSynchronizer
{
    /** Claves que toda entrada de metrics_by_currency debe tener. */
    private const CURRENCY_METRICS_TEMPLATE = [
        'total_sales' => 0.0,
        'total_cards' => 0,
        'prize_pool' => 0.0,
        'full_house_prize' => 0.0,
        'rke_commission' => 0.0,
        'room_commission' => 0.0,
        'seller_commission' => 0.0,
        'jackpot_contribution' => 0.0,
    ];

    /**
     * Cuenta participantes reales con cartones en lotes vigentes.
     * Los compradores externos se cuentan por ExternalPlayerParticipation;
     * la fila room_user que los agrupa representa al vendedor, no al comprador.
     */
    public function countPlayers(BingoRoom|string $room) : int
    {
        $roomId = $room instanceof BingoRoom ? (string) $room->id : $room;

        return $this->countPlayersExcludingLot($roomId, null);
    }

    /**
     * Cuenta participantes registrados para aplicar max_players durante una
     * compra. Incluye al comprador externo que se está procesando aunque sus
     * cartones/participaciones aún no se hayan insertado.
     */
    public function countPlayersForCapacity(string $roomId, ?string $pendingExternalPlayerId = null, ?string $pendingUserId = null) : int
    {
        $internalQuery = BingoRoomUser::query()
            ->where('room_id', $roomId)
            ->whereIn('role', [BingoRoomUser::ROLE_PLAYER, BingoRoomUser::ROLE_PLAYER_SELLER])
            ->where(function($query) use ($roomId, $pendingUserId) {
                $query->whereExists(fn ($lots) => $lots->selectRaw('1')
                    ->from('bingo_card_lots')
                    ->whereColumn('bingo_card_lots.room_user_id', 'bingo_room_users.id')
                    ->where('bingo_card_lots.room_id', $roomId)
                    ->where('bingo_card_lots.status', 'ACTIVE'));

                if ($pendingUserId !== null) {
                    $query->orWhere('user_id', $pendingUserId);
                }
            });

        $internalUserIds = $internalQuery->distinct()->pluck('user_id')->map(fn ($id) => (string) $id);

        $externalQuery = DB::table('external_player_participations')
            ->join('bingo_cards', function($join) {
                $join->on(
                    DB::raw('JSON_UNQUOTE(external_player_participations.participable_id)'),
                    '=',
                    'bingo_cards.id',
                );
            })
            ->join('bingo_card_lots', 'bingo_card_lots.id', '=', 'bingo_cards.card_lot_id')
            ->where('external_player_participations.participable_type', BingoCard::class)
            ->whereNull('external_player_participations.deleted_at')
            ->whereNull('bingo_cards.deleted_at')
            ->whereNull('bingo_card_lots.deleted_at')
            ->where('bingo_cards.room_id', $roomId)
            ->where('bingo_card_lots.status', 'ACTIVE');

        $externalIds = $externalQuery->distinct()->pluck('external_player_participations.external_player_id');
        if ($pendingExternalPlayerId !== null) {
            $externalIds->push($pendingExternalPlayerId);
        }

        $externalParticipants = $externalIds->isEmpty()
            ? collect()
            : ExternalPlayer::query()
                ->whereIn('id', $externalIds->unique())
                ->get(['id', 'converted_user_id'])
                ->map(fn (ExternalPlayer $player) => $player->converted_user_id
                    ? (string) $player->converted_user_id
                    : 'external:' . (string) $player->id);

        return $internalUserIds->concat($externalParticipants)->unique()->count();
    }

    /** Número de cartones que pertenecen a lotes aún vigentes. */
    public function countCards(BingoRoom|string $room) : int
    {
        $roomId = $room instanceof BingoRoom ? (string) $room->id : $room;

        return (int) BingoCardLot::query()
            ->where('room_id', $roomId)
            ->where('status', 'ACTIVE')
            ->sum('card_count');
    }

    /**
     * Recalcula contadores y métricas financieras. Si hay lotes vigentes
     * antiguos sin financial_breakdown, conserva los importes existentes:
     * inventar ceros borraría historia que no se puede reconstruir con certeza.
     *
     * @return array{total_players:int,total_cards:int,financials_reconciled:bool,legacy_lots:list<string>}
     */
    public function synchronize(BingoRoom|string $room, ?string $excludeLotId = null) : array
    {
        return DB::transaction(function() use ($room, $excludeLotId) : array {
            $roomId = $room instanceof BingoRoom ? (string) $room->id : $room;
            $locked = BingoRoom::query()->whereKey($roomId)->lockForUpdate()->firstOrFail();

            $lotsQuery = BingoCardLot::query()
                ->where('room_id', $roomId)
                ->where('status', 'ACTIVE');

            if ($excludeLotId !== null) {
                $lotsQuery->where('id', '!=', $excludeLotId);
            }

            $lots = $lotsQuery->get([
                'id',
                'card_count',
                'sale_amount',
                'currency_id',
                'financial_breakdown',
            ]);

            $totalCards = (int) $lots->sum('card_count');
            $totalPlayers = $this->countPlayersExcludingLot($roomId, $excludeLotId);
            $legacyLots = [];
            $usd = [
                'total_sales' => 0.0,
                'rke_commission' => 0.0,
                'room_commission' => 0.0,
                'seller_commission' => 0.0,
                'subsidiary_commission' => 0.0,
                'commerce_commission' => 0.0,
                'prize_pool' => 0.0,
            ];
            $byCurrency = [];

            foreach ($lots as $lot) {
                $breakdown = $lot->financial_breakdown;
                if (! is_array($breakdown) || ($breakdown['version'] ?? null) !== 1) {
                    if ((float) $lot->sale_amount > 0) {
                        $legacyLots[] = (string) $lot->id;
                    }
                    continue;
                }

                if (($breakdown['channel'] ?? null) === 'demo') {
                    continue;
                }

                $roomUsd = $breakdown['room_usd'] ?? null;
                if (! is_array($roomUsd)) {
                    $legacyLots[] = (string) $lot->id;
                    continue;
                }

                foreach (array_keys($usd) as $key) {
                    $usd[$key] += (float) ($roomUsd[$key] ?? 0);
                }

                $currencyId = (string) ($breakdown['currency_id'] ?? $lot->currency_id ?? '');
                $roomLocal = $breakdown['room_local'] ?? null;
                if ($currencyId !== '' && is_array($roomLocal)) {
                    // Mismas claves base que RoomMetricsService::updateRoomMetricsWithCommissions()
                    // lee sin `?? 0` en la siguiente venta; el desglose del lote no trae
                    // full_house_prize (es el mismo bote), así que se deriva de prize_pool.
                    $current = $byCurrency[$currencyId] ?? self::CURRENCY_METRICS_TEMPLATE;
                    foreach ($roomLocal as $key => $value) {
                        if (is_numeric($value)) {
                            $current[$key] = round((float) ($current[$key] ?? 0) + (float) $value, 3);
                        }
                    }
                    $current['total_cards'] = (int) $current['total_cards'];
                    $current['full_house_prize'] = $current['prize_pool'];
                    $byCurrency[$currencyId] = $current;
                }
            }

            $update = [
                'total_players' => $totalPlayers,
                'total_cards' => $totalCards,
            ];
            $financialsReconciled = $legacyLots === [];

            if ($financialsReconciled) {
                $initialPrizePool = 0.0;
                $config = BingoRoomConfig::query()->where('room_id', $roomId)->first();
                if ($config && $config->amount_added_prize_pool !== null) {
                    $type = PrizePoolType::find($config->prize_pool_type_id);
                    if ($type && ! $type->isAccumulative()) {
                        $initialPrizePool = (float) $config->amount_added_prize_pool;
                    }
                }

                foreach (['total_sales', 'rke_commission', 'room_commission', 'seller_commission', 'subsidiary_commission', 'commerce_commission'] as $field) {
                    $update[$field] = round($usd[$field], 2);
                }

                $update['prize_pool'] = round($initialPrizePool + $usd['prize_pool'], 2);
                $update['full_house_prize'] = round($usd['prize_pool'], 2);
                $update['metrics_by_currency'] = $byCurrency;
            } else {
                Log::warning('[BingoRoomMetricsSynchronizer] Métricas financieras no recalculadas: hay lotes activos sin desglose histórico', [
                    'room_id' => $roomId,
                    'legacy_lot_ids' => $legacyLots,
                ]);
            }

            $locked->forceFill($update)->save();

            return [
                'total_players' => $totalPlayers,
                'total_cards' => $totalCards,
                'financials_reconciled' => $financialsReconciled,
                'legacy_lots' => $legacyLots,
            ];
        });
    }

    private function countPlayersExcludingLot(string $roomId, ?string $excludeLotId) : int
    {
        $activeLots = BingoCardLot::query()
            ->where('room_id', $roomId)
            ->where('status', 'ACTIVE')
            ->when($excludeLotId !== null, fn ($q) => $q->where('id', '!=', $excludeLotId))
            ->get(['id', 'room_user_id']);

        $roomUserIds = $activeLots->pluck('room_user_id')->map(fn ($id) => (string) $id)->unique();
        $internalUserIds = $roomUserIds->isEmpty()
            ? collect()
            : BingoRoomUser::query()
                ->where('room_id', $roomId)
                ->whereIn('id', $roomUserIds)
                ->whereIn('role', [BingoRoomUser::ROLE_PLAYER, BingoRoomUser::ROLE_PLAYER_SELLER])
                ->distinct()
                ->pluck('user_id')
                ->map(fn ($id) => (string) $id);

        $externalQuery = DB::table('external_player_participations')
            ->join('bingo_cards', function($join) {
                $join->on(
                    DB::raw('JSON_UNQUOTE(external_player_participations.participable_id)'),
                    '=',
                    'bingo_cards.id',
                );
            })
            ->join('bingo_card_lots', 'bingo_card_lots.id', '=', 'bingo_cards.card_lot_id')
            ->where('external_player_participations.participable_type', BingoCard::class)
            ->whereNull('external_player_participations.deleted_at')
            ->where('bingo_cards.room_id', $roomId)
            ->where('bingo_card_lots.status', 'ACTIVE');

        if ($excludeLotId !== null) {
            $externalQuery->where('bingo_card_lots.id', '!=', $excludeLotId);
        }

        $externalIds = $externalQuery->distinct()->pluck('external_player_participations.external_player_id');
        $externalParticipants = $externalIds->isEmpty()
            ? collect()
            : ExternalPlayer::query()
                ->whereIn('id', $externalIds)
                ->get(['id', 'converted_user_id'])
                ->map(fn (ExternalPlayer $player) => $player->converted_user_id
                    ? (string) $player->converted_user_id
                    : 'external:' . (string) $player->id);

        return $internalUserIds->concat($externalParticipants)->unique()->count();
    }
}