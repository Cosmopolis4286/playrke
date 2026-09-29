<?php

namespace App\Domains\Games\Bingo\Services\WinEvent;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrizePayment;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Platform\WinEvent\Contracts\WinEventDetailResolver;
use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Platform\WinEvent\Support\WinEventDetailSection as Section;

/**
 * Detalle de una ganancia de Bingo. BingoDrawService reporta el evento con
 * source_reference = bingo_card_prizes.id y metadata {room_id, pattern}.
 */
final class BingoWinEventDetailResolver implements WinEventDetailResolver
{
    private const PRIZE_STATUS_VARIANTS = [
        'waiting'    => 'warning',
        'processing' => 'info',
        'claimed'    => 'success',
        'cancelled'  => 'secondary',
        'suspended'  => 'danger',
        'disputed'   => 'danger',
    ];

    private const PAYMENT_STATUS_VARIANTS = [
        'pending'   => 'warning',
        'completed' => 'success',
        'failed'    => 'danger',
        'cancelled' => 'secondary',
    ];

    public function gameSlug(): string
    {
        return 'bingo';
    }

    public function consumedMetadataKeys(): array
    {
        return ['room_id', 'pattern'];
    }

    public function resolve(WinEvent $winEvent): array
    {
        $prize = $winEvent->source_reference
            ? BingoCardPrize::withTrashed()
                ->with([
                    'room.status',
                    'round.bingoPattern',
                    'winningCard',
                    'seller:id,first_name,last_name,username',
                    'currency:id,iso_code,symbol,decimal_places',
                    'payments' => fn ($q) => $q->latest('created_at'),
                ])
                ->find($winEvent->source_reference)
            : null;

        $room = $prize?->room
            ?? (isset($winEvent->metadata['room_id']) ? BingoRoom::with('status')->find($winEvent->metadata['room_id']) : null);

        return array_values(array_filter([
            $this->prizeSection($winEvent, $prize),
            $room ? $this->roomSection($room) : null,
            $prize?->winningCard ? $this->cardSection($prize) : null,
            $prize ? $this->paymentsSection($prize) : null,
        ]));
    }

    private function prizeSection(WinEvent $winEvent, ?BingoCardPrize $prize): Section
    {
        $section = Section::make('bingo-prize', 'Premio de Bingo', 'fas fa-trophy')
            ->item('Patrón ganador', $prize?->round?->bingoPattern?->name ?? $prize?->winning_pattern ?? $winEvent->metadata['pattern'] ?? null);

        if (! $prize) {
            return $section->item('Registro de premio', 'No encontrado (source_reference vacío o eliminado)', Section::TYPE_BADGE, 'secondary');
        }

        $symbol = $prize->currency?->symbol ?? '';
        $decimals = (int) ($prize->currency?->decimal_places ?? 2);

        return $section
            ->item('Tipo de premio', $prize->prize_type_description, Section::TYPE_BADGE, 'info')
            ->item('Ronda', $prize->round?->round ? "#{$prize->round->round}" : null)
            ->item('Monto USD', '$' . number_format((float) $prize->usd_amount, 2), Section::TYPE_MONEY)
            ->item('Monto local', $symbol . number_format((float) $prize->local_amount, $decimals) . ' ' . ($prize->currency?->iso_code ?? ''), Section::TYPE_MONEY)
            ->item('Estado del premio', $prize->prize_status_description, Section::TYPE_BADGE, self::PRIZE_STATUS_VARIANTS[$prize->prize_status] ?? 'secondary')
            ->item('Reclamado el', $prize->claimed_at?->toISOString(), Section::TYPE_DATETIME)
            ->item('Código de verificación', $prize->verification_code, Section::TYPE_CODE)
            ->item('Vendedor', $prize->seller?->full_name ?: $prize->seller?->username)
            ->item('Notas', $prize->prize_notes)
            ->item('ID del premio', (string) $prize->id, Section::TYPE_CODE);
    }

    private function roomSection(BingoRoom $room): Section
    {
        return Section::make('bingo-room', 'Sala', 'fas fa-door-open')
            ->item('Código de sala', $room->uuid_code, Section::TYPE_CODE)
            ->item('Estado', $room->status?->label ?? $room->status_code, Section::TYPE_BADGE, 'secondary')
            ->item('Jugadores', $room->total_players)
            ->item('Cartones vendidos', $room->total_cards)
            ->item('Ventas totales', $room->total_sales !== null ? '$' . number_format((float) $room->total_sales, 2) : null, Section::TYPE_MONEY)
            ->item('Pozo de premios', $room->prize_pool !== null ? '$' . number_format((float) $room->prize_pool, 2) : null, Section::TYPE_MONEY)
            ->item('Publicada', $room->published_at?->toISOString(), Section::TYPE_DATETIME)
            ->item('Finalizada', $room->finished_at?->toISOString(), Section::TYPE_DATETIME)
            ->item('ID de sala', (string) $room->id, Section::TYPE_CODE);
    }

    private function cardSection(BingoCardPrize $prize): Section
    {
        $card = $prize->winningCard;
        $numbers = $card->card_numbers;

        // BingoCardManagementService guarda json_encode() sobre un cast 'array':
        // según el origen del cartón puede volver como string JSON.
        if (is_string($numbers)) {
            $numbers = json_decode($numbers, true);
        }

        return Section::make('bingo-card', 'Cartón ganador', 'fas fa-th')
            ->item('Serial', $card->serial_number, Section::TYPE_CODE)
            ->item('Estado', $card->getCardStatusDescription(), Section::TYPE_BADGE, 'success')
            ->item('Cartón personalizado', $card->is_custom_card ? 'Sí' : null)
            ->item('Números', is_array($numbers) && $numbers !== [] ? $numbers : null, Section::TYPE_NUMBERS);
    }

    private function paymentsSection(BingoCardPrize $prize): Section
    {
        return Section::make('bingo-payments', 'Pagos del premio', 'fas fa-money-check-alt')
            ->table('Intentos de pago', [
                'method'    => 'Método',
                'amount'    => 'Monto',
                'status'    => 'Estado',
                'reference' => 'Referencia',
                'processed' => 'Procesado',
            ], $prize->payments->map(fn (BingoCardPrizePayment $p) => [
                'method'    => $p->payment_method_description,
                'amount'    => ($prize->currency?->symbol ?? '') . number_format((float) $p->payment_amount, (int) ($prize->currency?->decimal_places ?? 2)),
                'status'    => ['value' => $p->payment_status_description, 'variant' => self::PAYMENT_STATUS_VARIANTS[$p->payment_status] ?? 'secondary'],
                'reference' => $p->transaction_reference,
                'processed' => $p->processed_at?->toISOString(),
            ])->all());
    }
}
