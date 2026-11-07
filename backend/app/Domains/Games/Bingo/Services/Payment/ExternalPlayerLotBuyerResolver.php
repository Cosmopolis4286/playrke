<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Payment;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\ExternalPlayerParticipation;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Resuelve el comprador real de un lote vendido a jugadores externos.
 *
 * Un lote se genera para una sola compra. Si los cartones apuntan a más de
 * un ExternalPlayer (datos históricos inconsistentes o manipulación), no se
 * debe escoger arbitrariamente al primero para registrar una obligación de
 * devolución: el reembolso queda pendiente de conciliación manual.
 */
final class ExternalPlayerLotBuyerResolver
{
    public function resolve(BingoCardLot $lot) : ?ExternalPlayer
    {
        $cardIds = BingoCard::where('card_lot_id', (string) $lot->id)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();

        if ($cardIds->isEmpty()) {
            return null;
        }

        $externalPlayerIds = ExternalPlayerParticipation::withTrashed()
            ->ofParticipable(BingoCard::class)
            ->whereIn(DB::raw('JSON_UNQUOTE(participable_id)'), $cardIds->all())
            ->distinct()
            ->pluck('external_player_id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($externalPlayerIds->count() > 1) {
            throw new DomainException(
                "El lote #{$lot->id} está asociado a varios compradores externos; "
                . 'el reembolso automático se bloqueó para evitar asignar la devolución al comprador incorrecto.',
            );
        }

        $externalPlayerId = $externalPlayerIds->first();

        return $externalPlayerId
            ? ExternalPlayer::withTrashed()->find($externalPlayerId)
            : null;
    }
}
