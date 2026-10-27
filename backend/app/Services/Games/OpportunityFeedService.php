<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Domains\Games\Bingo\Services\Room\BingoRoomContextService;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\Currency;
use Illuminate\Support\Collection;
use Throwable;

/**
 * "Opportunity Feed" — combina salas activas de Bingo y Trivia en una sola
 * lista de oportunidades (entrada, premio, hora de inicio), sin tocar la
 * lógica de ninguno de los dos juegos (ver progreso/estrategia-producto-plan.md,
 * Fase 4). Es una vista de DESCUBRIMIENTO, no de compra — no resuelve precio
 * de cartón por jugador/moneda (eso lo sigue haciendo la pantalla de la sala
 * al entrar); solo expone lo que ya está disponible sin cálculo adicional.
 *
 * Bingo: reutiliza BingoRoomContextService::context('game-rooms', $userId) —
 * el mismo contexto que ya usa GameRooms.vue, con el filtrado por rol
 * (player/commerce/admin) ya resuelto ahí.
 *
 * Trivia: reutiliza TriviaRoom::scopeJoinable() (ya existía en el modelo,
 * pero ningún controlador lo usaba — TriviaGameController::lobby() construye
 * su propia query). TriviaRoom vive en la conexión 'pgsql_trivia' (Postgres),
 * separada de la conexión principal — no se puede resolver el símbolo de
 * moneda con una relación Eloquent cross-connection, se resuelve con una
 * query aparte a Currency (MySQL), mismo patrón que
 * PrizeDistributionService::convertToUsd().
 */
final class OpportunityFeedService
{
    public function __construct(
        private readonly BingoRoomContextService $bingoRoomContext,
    ) {
    }

    /**
     * @return array{opportunities: array<int, array>}
     */
    public function getFeed(int $userId) : array
    {
        $bingo = $this->bingoOpportunities($userId);
        $trivia = $this->triviaOpportunities();

        $opportunities = $bingo->concat($trivia)
            ->sortBy(fn (array $o) => $o['starts_at'] ?? PHP_INT_MAX)
            ->values();

        return ['opportunities' => $opportunities->all()];
    }

    private function bingoOpportunities(int $userId) : Collection
    {
        try {
            $dto = $this->bingoRoomContext->context('game-rooms', $userId);
        } catch (Throwable) {
            // El contexto de Bingo requiere rol resuelto (player/commerce/admin) —
            // si el usuario no califica para ese contexto, no se muestran
            // oportunidades de Bingo en vez de romper el feed completo.
            return collect();
        }

        return collect($dto->rooms ?? [])->map(function($room) use ($dto) {
            return [
                'game' => 'bingo',
                'game_label' => 'Bingo',
                'room_id' => (string) $room->id,
                'room_code' => $room->uuid_code,
                'entry' => null, // precio de cartón depende de rol/moneda — no se resuelve aquí, ver docblock de clase
                'prize' => (float) ($room->prize_pool ?? $room->full_house_prize ?? 0),
                'currency_symbol' => $dto->currencySym,
                'starts_at' => $room->config?->unix_date,
                'total_players' => (int) ($room->total_players ?? 0),
            ];
        });
    }

    private function triviaOpportunities() : Collection
    {
        $rooms = TriviaRoom::query()
            ->joinable()
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get(['id', 'code', 'title', 'entry_fee', 'entry_currency_id', 'prize_amount', 'prize_currency_id', 'prize_type', 'scheduled_at', 'current_players', 'max_players']);

        if ($rooms->isEmpty()) {
            return collect();
        }

        $currencyIds = $rooms->pluck('prize_currency_id')->merge($rooms->pluck('entry_currency_id'))->filter()->unique();
        $currencies = Currency::whereIn('id', $currencyIds)->get(['id', 'symbol'])->keyBy('id');

        return $rooms->map(function(TriviaRoom $room) use ($currencies) {
            return [
                'game' => 'trivia',
                'game_label' => 'Trivia',
                'room_id' => (string) $room->id,
                'room_code' => $room->code,
                'title' => $room->title,
                'entry' => $room->entry_fee !== null ? (float) $room->entry_fee : null,
                'entry_currency_symbol' => $currencies->get($room->entry_currency_id)?->symbol,
                'prize' => $room->prize_amount !== null ? (float) $room->prize_amount : null,
                'currency_symbol' => $currencies->get($room->prize_currency_id)?->symbol,
                'starts_at' => $room->scheduled_at?->timestamp,
                'total_players' => (int) ($room->current_players ?? 0),
                'max_players' => $room->max_players,
            ];
        });
    }
}
