<?php

namespace App\Services\Games\Bingo\History;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use Illuminate\Pagination\LengthAwarePaginator;

final class PlayerGameHistoryService
{
    /**
     * Historial paginado de partidas del jugador (listado).
     */
    public function getHistory(string $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $paginator = BingoRoomUser::with(['room.config', 'room.status'])
            ->where('user_id', $userId)
            ->whereIn('role', [BingoRoomUser::ROLE_PLAYER, BingoRoomUser::ROLE_PLAYER_SELLER])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $roomIds = $paginator->getCollection()->pluck('room_id')->unique()->values()->all();

        $prizesByRoom = BingoCardPrize::whereIn('room_id', $roomIds)
            ->where('creator_user_id', $userId)
            ->with('currency')
            ->get()
            ->groupBy('room_id');

        $paginator->getCollection()->transform(
            fn(BingoRoomUser $ru) => $this->formatRow($ru, $prizesByRoom->get($ru->room_id, collect()))
        );

        return $paginator;
    }

    /**
     * Detalle completo de una partida: rondas + cartones del jugador + cartones ganadores.
     */
    public function getDetail(string $userId, string $roomId): array
    {
        $room = BingoRoom::with([
            'config.roomType',
            'config.gameMode',
            'config.prizePoolType',
            'status',
            'rounds.prizes.currency',
        ])->find($roomId);

        if (!$room) {
            abort(404, 'Sala no encontrada.');
        }

        $ru = BingoRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereIn('role', [BingoRoomUser::ROLE_PLAYER, BingoRoomUser::ROLE_PLAYER_SELLER])
            ->first();

        if (!$ru) {
            abort(403, 'No participaste en esta sala.');
        }

        // Cartones del jugador en esta sala
        $playerCards = BingoCard::where('room_id', $roomId)
            ->where('creator_user_id', $userId)
            ->orderBy('status', 'desc') // ganadores primero
            ->get()
            ->map(fn(BingoCard $c) => [
                'id'           => (string) $c->id,
                'serial'       => $c->serial_number,
                'status'       => (int) $c->status,
                'is_winner'    => $c->isWinner(),
                'card_matrix'  => $c->card_numbers ?? [],
            ])
            ->values()
            ->all();

        // Premios del jugador
        $prizes = BingoCardPrize::where('room_id', $roomId)
            ->where('creator_user_id', $userId)
            ->with(['currency', 'winningCard'])
            ->get()
            ->map(fn(BingoCardPrize $p) => [
                'type'           => $p->prize_type_description,
                'prize_type'     => $p->prize_type,
                'usd_amount'     => (float) $p->usd_amount,
                'local_amount'   => (float) $p->local_amount,
                'currency'       => $p->currency?->iso_code ?? 'USD',
                'currency_symbol'=> $p->currency?->symbol ?? '$',
                'decimal_places' => $p->currency?->decimal_places ?? 2,
                'is_usd'         => ($p->currency?->iso_code ?? 'USD') === 'USD',
                'status'         => $p->prize_status,
                'card_id'        => $p->winning_card_id ? (string) $p->winning_card_id : null,
                'card_serial'    => $p->winningCard?->serial_number,
                'card_matrix'    => $p->winningCard?->card_numbers ?? [],
            ])
            ->values()
            ->all();

        // Rondas
        $config  = $room->config;
        $rounds  = $room->rounds
            ->sortBy('round')
            ->map(fn(BingoRound $r) => [
                'round'        => $r->round,
                'status'       => $r->status,
                'prize_type'   => $r->prize_type,
                'pattern_id'   => $r->bingo_pattern_id,
            ])
            ->values()
            ->all();

        // Fecha
        $dateStr = null;
        if ($config?->date) {
            $d = $config->date instanceof \Carbon\Carbon ? $config->date->format('Y-m-d') : (string) $config->date;
            $dateStr = $d . ' ' . ($config->time ?? '00:00');
        }

        return [
            'room_id'         => (string) $room->id,
            'room_code'       => $room->uuid_code,
            'game_date'       => $dateStr,
            'room_status'     => $room->status?->code,
            'room_type'       => $config?->roomType?->label,
            'game_mode'       => $config?->gameMode?->label,
            'prize_pool_type' => $config?->prizePoolType?->label
                ? preg_replace('/^Bote\s+/i', '', $config->prizePoolType->label)
                : null,
            'prize_pool'      => (float) $room->prize_pool,
            'game_variant'    => $config?->game_variant ?? 'BINGO_75',
            'cards_purchased'  => (int) $ru->cards_purchased,
            'won'              => $ru->winner === 'Y' || count($prizes) > 0,
            'total_usd'        => round((float) collect($prizes)->sum('usd_amount'), 2),
            'total_local'      => round((float) collect($prizes)->sum('local_amount'), 4),
            'currency'         => collect($prizes)->first()['currency'] ?? 'USD',
            'currency_symbol'  => collect($prizes)->first()['currency_symbol'] ?? '$',
            'decimal_places'   => collect($prizes)->first()['decimal_places'] ?? 2,
            'is_usd'           => (collect($prizes)->first()['currency'] ?? 'USD') === 'USD',
            'rounds'           => $rounds,
            'player_cards'     => $playerCards,
            'prizes'           => $prizes,
        ];
    }

    private function formatRow(BingoRoomUser $ru, $prizes): array
    {
        $room   = $ru->room;
        $config = $room?->config;

        $gameDate = null;
        if ($config?->date) {
            $d = $config->date instanceof \Carbon\Carbon ? $config->date->format('Y-m-d') : (string) $config->date;
            $gameDate = $d . ' ' . ($config->time ?? '00:00');
        }

        $wonCount   = $prizes->count();
        $totalUsd   = $prizes->sum('usd_amount');
        $totalLocal = $prizes->sum('local_amount');

        $firstPrize   = $prizes->first();
        $currency     = $firstPrize?->currency;
        $isUsd        = ($currency?->iso_code ?? 'USD') === 'USD';

        return [
            'room_id'         => (string) $ru->room_id,
            'room_code'       => $room?->uuid_code ?? (string) $ru->room_id,
            'game_date'       => $gameDate,
            'room_status'     => $room?->status?->code ?? null,
            'start_mode'      => $config?->start_mode ?? 'SCHEDULED',
            'game_variant'    => $config?->game_variant ?? 'BINGO_75',
            'cards_purchased' => (int) $ru->cards_purchased,
            'won'             => $ru->winner === 'Y' || $wonCount > 0,
            'prizes_count'    => $wonCount,
            'total_usd'       => round((float) $totalUsd, 2),
            'total_local'     => round((float) $totalLocal, 4),
            'currency'        => $currency?->iso_code ?? 'USD',
            'currency_symbol' => $currency?->symbol ?? '$',
            'decimal_places'  => $currency?->decimal_places ?? 2,
            'is_usd'          => $isUsd,
            'joined_at'       => $ru->created_at?->toIso8601String(),
        ];
    }
}
