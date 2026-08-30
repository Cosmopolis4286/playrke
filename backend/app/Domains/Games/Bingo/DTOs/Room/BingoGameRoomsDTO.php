<?php

namespace App\Domains\Games\Bingo\DTOs\Room;

use App\DTOs\ContextDTO;
use Illuminate\Support\Collection;

/**
 * DTO para la vista de salas de juego de bingo (player-facing).
 * Distinto del BingoRoomSummaryDTO (admin/commerce CRUD).
 */
final class BingoGameRoomsDTO implements ContextDTO
{
    public function __construct(
        /** @var Collection Salas activas con config y players_room */
        public readonly Collection $rooms,
        /** Tasa de cambio para mostrar prizes en moneda local */
        public readonly float $exchangeRate,
        /** Símbolo de moneda */
        public readonly string $currencySym,
        /** Token del usuario */
        public readonly ?string $userToken,
        /** Datos de fecha/hora del servidor + lista de salas para countdown */
        public readonly ?array $dateTimeRooms,
        /** Precios del cartón para jugadores (por sala) */
        public readonly ?array $cardPrices,
        /** Texto del reglamento del bingo (HTML, configurable por admin) */
        public readonly string $bingoRules = '',
        /** Path relativo de la imagen del reglamento del bingo */
        public readonly string $imageBingoRules = '',
    ) {}

    public function toView(): array
    {
        $roomsWithPrices = $this->rooms->map(function($room) {
            // Mapear relación players → players_room con campo card_count esperado por el frontend
            $playersRoom = $room->relationLoaded('players')
                ? $room->players->map(fn($p) => ['card_count' => (int) $p->cards_purchased])->values()->all()
                : null;

            // Preview de jugadores para prueba social (avatares/iniciales en la card del lobby):
            // solo la inicial del nombre, nunca username/email completos. Se trunca a 4.
            $playersPreview = $room->relationLoaded('playersPreview')
                ? $room->playersPreview
                    ->take(4)
                    ->map(function ($p) {
                        $name = $p->user?->first_name ?: $p->user?->username;
                        return ['initial' => $name ? mb_strtoupper(mb_substr($name, 0, 1)) : '?'];
                    })
                    ->values()
                    ->all()
                : [];

            $roomData = array_merge(
                $room->toArray(),
                [
                    'status_code'      => $room->status?->code ?? 'ACTIVE',
                    'room_type'        => $room->config?->roomType?->code ?? 'PUBLIC',
                    'game_mode'        => $room->config?->connectionType?->code ?? null,
                    'id'               => $room->getRouteKey(), // Usar ID hasheado para route binding
                    'players_room'     => $playersRoom,
                    'players_preview'  => $playersPreview,
                    // Timestamp Unix (no string ISO) — mismo criterio que config.unix_date,
                    // evita parsear fechas en el frontend. Usado como fallback de "abierta
                    // hace X" cuando la sala no tiene fecha/hora programada (start_mode MANUAL).
                    'unix_created_at'  => $room->created_at?->timestamp,
                ],
            );

            // Agregar precio del cartón si está disponible para esta sala
            if ($this->cardPrices && isset($this->cardPrices[$room->id])) {
                $roomData['card_price'] = $this->cardPrices[$room->id]['card_price'];
                $roomData['currency_symbol'] = $this->cardPrices[$room->id]['currency_symbol'];
            }

            // Código semántico del tipo de bote (ACCUMULATIVE/FIXED/MIXED/PRIZE) — prize_pool_type_id
            // es un snowflake ID sin significado propio, el frontend necesita el code de la relación.
            if (isset($roomData['config']) && is_array($roomData['config'])) {
                $roomData['config']['prize_pool_type'] = $room->config?->prizePoolType?->code;
            }

            // Progreso de venta + pozo en vivo ("queda un X%" / barra de pozo
            // creciente) — ambos se derivan de campos ya cargados (total_cards,
            // prize_pool, max_cards_to_start), sin queries adicionales. max_cards_to_start
            // es opcional en la config (salas sin tope de venta): sin él no hay
            // base para proyectar ningún porcentaje.
            $maxCards = $room->config?->max_cards_to_start;
            if ($maxCards && $maxCards > 0) {
                $totalCards = (int) ($room->total_cards ?? 0);
                $prizePool  = (float) ($room->prize_pool ?? 0);

                $roomData['cards_sold_pct'] = round(min(100, ($totalCards / $maxCards) * 100), 2);

                // Proyección lineal: si el pozo actual corresponde a $totalCards
                // cartones vendidos, al 100% (max_cards_to_start) sería
                // proporcionalmente mayor — mismo criterio que la barra "Progreso
                // del pozo" de Bingo Tocuyo, no una meta fija independiente.
                if ($totalCards > 0) {
                    $roomData['prize_pool_projected'] = round($prizePool * ($maxCards / $totalCards), 2);
                    $roomData['prize_pool_pct'] = round(min(100, ($prizePool / $roomData['prize_pool_projected']) * 100), 2);
                } else {
                    $roomData['prize_pool_projected'] = 0.0;
                    $roomData['prize_pool_pct'] = 0.0;
                }
            }

            return $roomData;
        })->values()->all();

        return [
            'title'         => 'Salas de Juego',
            'rooms'         => $roomsWithPrices,
            'exchangeRate'  => $this->exchangeRate,
            'currencySym'   => $this->currencySym,
            'userToken'     => $this->userToken,
            'dateTimeRooms' => $this->dateTimeRooms,
            'cardPrices'    => $this->cardPrices,
            'bingoRules'      => $this->bingoRules,
            'imageBingoRules' => $this->imageBingoRules,
        ];
    }
}
