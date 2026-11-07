<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Room\Metrics;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\Roles\Player\External\ExternalPlayer;
use Illuminate\Support\Facades\DB;

/**
 * Punto de entrada único para reconstruir los contadores denormalizados de una sala.
 *
 * La fuente de verdad son los lotes ACTIVE, los room users vinculados a esos lotes
 * y las participaciones externas de sus cartones. No acepta deltas: repetir la
 * operación produce el mismo resultado (idempotencia) y el lock de la sala
 * serializa sincronizaciones concurrentes.
 */
final class BingoRoomStateCoordinator
{
    /**
     * @return array{total_players:int,total_cards:int}
     */
    public function synchronizeCounters(BingoRoom|string $room) : array
    {
        $roomId = $room instanceof BingoRoom ? (string) $room->id : $room;

        return DB::transaction(function () use ($roomId): array {
            $lockedRoom = BingoRoom::query()->whereKey($roomId)->lockForUpdate()->firstOrFail();

            $activeLots = BingoCardLot::query()
                ->where('room_id', $roomId)
                ->where('status', 'ACTIVE')
                ->get(['id', 'card_count', 'room_user_id']);

            $totalCards = (int) $activeLots->sum('card_count');

            $currencyCardCounts = BingoCardLot::query()
                ->where('room_id', $roomId)
                ->where('status', 'ACTIVE')
                ->whereNotNull('currency_id')
                ->selectRaw('currency_id, COALESCE(SUM(card_count), 0) as card_count')
                ->groupBy('currency_id')
                ->pluck('card_count', 'currency_id');

            $metricsByCurrency = (array) ($lockedRoom->metrics_by_currency ?? []);
            foreach ($metricsByCurrency as $currencyId => $currencyMetrics) {
                $currencyMetrics = (array) $currencyMetrics;
                $currencyMetrics['total_cards'] = (int) ($currencyCardCounts[(string) $currencyId] ?? 0);
                $metricsByCurrency[$currencyId] = $currencyMetrics;
            }
            foreach ($currencyCardCounts as $currencyId => $cardCount) {
                $currencyMetrics = (array) ($metricsByCurrency[(string) $currencyId] ?? []);
                $currencyMetrics['total_cards'] = (int) $cardCount;
                $metricsByCurrency[(string) $currencyId] = $currencyMetrics;
            }
            $roomUserIds = $activeLots->pluck('room_user_id')->filter()->map(fn ($id) => (string) $id)->unique();

            $internalPlayerIds = $roomUserIds->isEmpty()
                ? collect()
                : BingoRoomUser::query()
                    ->where('room_id', $roomId)
                    ->whereIn('id', $roomUserIds)
                    ->whereIn('role', [
                        BingoRoomUser::ROLE_PLAYER,
                        BingoRoomUser::ROLE_PLAYER_SELLER,
                    ])
                    ->distinct()
                    ->pluck('user_id')
                    ->map(fn ($id) => (string) $id);

            $externalIds = DB::table('external_player_participations')
                ->join('bingo_cards', function ($join) {
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
                ->where('bingo_card_lots.status', 'ACTIVE')
                ->distinct()
                ->pluck('external_player_participations.external_player_id');

            $externalPlayerKeys = $externalIds->isEmpty()
                ? collect()
                : ExternalPlayer::query()
                    ->whereIn('id', $externalIds)
                    ->get(['id', 'converted_user_id'])
                    ->map(fn (ExternalPlayer $player) => $player->converted_user_id
                        ? (string) $player->converted_user_id
                        : 'external:' . (string) $player->id);

            $totalPlayers = $internalPlayerIds->concat($externalPlayerKeys)->unique()->count();

            $lockedRoom->forceFill([
                'total_players' => $totalPlayers,
                'total_cards' => $totalCards,
                'metrics_by_currency' => $metricsByCurrency,
            ])->save();

            return [
                'total_players' => $totalPlayers,
                'total_cards' => $totalCards,
            ];
        }, 3);
    }


    /**
     * Cuenta participantes actuales más el comprador de la operación en curso.
     * Evita que el POS cuente al vendedor como jugador: solo incorpora su
     * comprador externo; los usuarios internos se incorporan si su rol es jugador.
     */
    public function countPlayersForCapacity(
        string $roomId,
        ?string $pendingUserId = null,
        ?string $pendingExternalPlayerId = null,
    ) : int {
        $count = $this->countPlayers($roomId);

        if ($pendingUserId !== null) {
            $pendingIsPlayer = BingoRoomUser::query()
                ->where('room_id', $roomId)
                ->where('user_id', $pendingUserId)
                ->whereIn('role', [BingoRoomUser::ROLE_PLAYER, BingoRoomUser::ROLE_PLAYER_SELLER])
                ->exists();

            $alreadyParticipates = DB::table('bingo_room_users')
                ->join('bingo_card_lots', 'bingo_card_lots.room_user_id', '=', 'bingo_room_users.id')
                ->where('bingo_room_users.room_id', $roomId)
                ->where('bingo_room_users.user_id', $pendingUserId)
                ->whereIn('bingo_room_users.role', [BingoRoomUser::ROLE_PLAYER, BingoRoomUser::ROLE_PLAYER_SELLER])
                ->where('bingo_card_lots.status', 'ACTIVE')
                ->exists();

            if ($pendingIsPlayer && ! $alreadyParticipates) {
                $count++;
            }
        }

        if ($pendingExternalPlayerId !== null) {
            $external = ExternalPlayer::query()->find($pendingExternalPlayerId);
            if ($external) {
                $alreadyParticipates = DB::table('external_player_participations')
                    ->join('bingo_cards', function ($join) {
                        $join->on(
                            DB::raw('JSON_UNQUOTE(external_player_participations.participable_id)'),
                            '=',
                            'bingo_cards.id',
                        );
                    })
                    ->join('bingo_card_lots', 'bingo_card_lots.id', '=', 'bingo_cards.card_lot_id')
                    ->where('external_player_participations.external_player_id', $pendingExternalPlayerId)
                    ->where('external_player_participations.participable_type', BingoCard::class)
                    ->whereNull('external_player_participations.deleted_at')
                    ->where('bingo_cards.room_id', $roomId)
                    ->where('bingo_card_lots.status', 'ACTIVE')
                    ->exists();

                $convertedUserAlreadyParticipates = $external->converted_user_id
                    ? DB::table('bingo_room_users')
                        ->join('bingo_card_lots', 'bingo_card_lots.room_user_id', '=', 'bingo_room_users.id')
                        ->where('bingo_room_users.room_id', $roomId)
                        ->where('bingo_room_users.user_id', $external->converted_user_id)
                        ->whereIn('bingo_room_users.role', [BingoRoomUser::ROLE_PLAYER, BingoRoomUser::ROLE_PLAYER_SELLER])
                        ->where('bingo_card_lots.status', 'ACTIVE')
                        ->exists()
                    : false;

                if (! $alreadyParticipates && ! $convertedUserAlreadyParticipates) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function countPlayers(BingoRoom|string $room) : int
    {
        return $this->synchronizeCounters($room)['total_players'];
    }

    public function countCards(BingoRoom|string $room) : int
    {
        return $this->synchronizeCounters($room)['total_cards'];
    }
}
