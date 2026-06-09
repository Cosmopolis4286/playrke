<?php

namespace App\Services\Games\Bingo\History;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

final class PlayerGameHistoryService
{
    /**
     * Devuelve el historial paginado de partidas de bingo del jugador.
     *
     * Cada entrada representa una sala en la que el usuario participó,
     * enriquecida con: fecha, cartones comprados, si ganó y premios.
     */
    public function getHistory(string $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $paginator = BingoRoomUser::with([
                'room.config',
                'room.status',
            ])
            ->where('user_id', $userId)
            ->whereIn('role', [
                BingoRoomUser::ROLE_PLAYER,
                BingoRoomUser::ROLE_PLAYER_SELLER,
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Cargar premios del jugador en batch (evita N+1)
        $roomIds = $paginator->getCollection()->pluck('room_id')->unique()->values()->all();

        $prizesByRoom = BingoCardPrize::whereIn('room_id', $roomIds)
            ->where('creator_user_id', $userId)
            ->with('currency')
            ->get()
            ->groupBy('room_id');

        $paginator->getCollection()->transform(function (BingoRoomUser $ru) use ($prizesByRoom) {
            return $this->formatRow($ru, $prizesByRoom->get($ru->room_id, collect()));
        });

        return $paginator;
    }

    private function formatRow(BingoRoomUser $ru, $prizes): array
    {
        $room   = $ru->room;
        $config = $room?->config;

        // Fecha de la partida
        $gameDate = null;
        if ($config?->date) {
            $dateStr = $config->date instanceof \Carbon\Carbon
                ? $config->date->format('Y-m-d')
                : (string) $config->date;
            $timeStr = $config->time ?? '00:00';
            $gameDate = "{$dateStr} {$timeStr}";
        }

        // Premios del jugador en esta sala
        $wonCount    = $prizes->count();
        $totalUsd    = $prizes->sum('usd_amount');
        $totalLocal  = $prizes->sum('local_amount');
        $prizeDetail = $prizes->map(fn($p) => [
            'type'        => $p->prize_type_description,
            'usd_amount'  => (float) $p->usd_amount,
            'local_amount'=> (float) $p->local_amount,
            'currency'    => $p->currency?->iso_code ?? '',
            'status'      => $p->prize_status,
        ])->values()->all();

        // Estado de la sala
        $roomStatus = $room?->status?->code ?? null;

        return [
            'room_id'         => (string) $ru->room_id,
            'room_code'       => $room?->uuid_code ?? (string) $ru->room_id,
            'game_date'       => $gameDate,
            'room_status'     => $roomStatus,
            'start_mode'      => $config?->start_mode ?? 'SCHEDULED',
            'cards_purchased' => (int) $ru->cards_purchased,
            'won'             => $ru->winner === 'Y' || $wonCount > 0,
            'prizes_count'    => $wonCount,
            'total_usd'       => round((float) $totalUsd, 2),
            'total_local'     => round((float) $totalLocal, 4),
            'prizes'          => $prizeDetail,
            'joined_at'       => $ru->created_at?->toIso8601String(),
        ];
    }
}
