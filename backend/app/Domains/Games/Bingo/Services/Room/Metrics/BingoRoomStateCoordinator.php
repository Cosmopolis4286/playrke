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
            ])->save();

            return [
                'total_players' => $totalPlayers,
                'total_cards' => $totalCards,
            ];
        }, 3);
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
