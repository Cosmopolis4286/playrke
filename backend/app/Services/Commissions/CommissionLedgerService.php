<?php

namespace App\Services\Commissions;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Models\Currency;
use App\Models\Platform\PlatformReceivable;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Histórico de comisiones de un usuario ("Mis comisiones"), armado desde lo
 * que ya registra cada venta (bingo_card_lots) — no es un libro aparte:
 *
 *  - seller:     commission_amount de sus ventas (seller_user_id).
 *  - subsidiary: subsidiary_commission_amount (snapshot subsidiary_id).
 *  - network:    commerce_commission_amount de la red de la comercializadora
 *                (snapshot commerce_id).
 *  - room:       comisión de sala como creador (financial_breakdown.room_local
 *                .room_commission de los lotes de sus salas).
 *
 * Cómo se cobró cada una (campo "how"):
 *  - cash:    venta fuera del sistema — la comisión ya la tiene en mano.
 *  - wallet:  compra con billetera del jugador — se acredita a su billetera
 *             al cerrar la sala (BingoRoomCreatorPayoutService).
 *  - custody: venta cruzada en su sala pública — la retuvo el comercio
 *             vendedor y se le paga al cobrar la deuda con la sala cerrada.
 * La billetera solo refleja lo que entra al sistema (wallet/custody); lo
 * cobrado en efectivo nunca se acredita, para no pagarlo dos veces.
 */
final class CommissionLedgerService
{
    public const SOURCES = ['seller', 'subsidiary', 'network', 'room'];

    /** Tope de filas por consulta (el rango de fechas acota el volumen). */
    private const MAX_ROWS = 5000;

    private const SOURCE_LABELS = [
        'seller' => 'Venta propia',
        'subsidiary' => 'Comisión de agencia',
        'network' => 'Comisión de red',
        'room' => 'Comisión de sala',
    ];

    /**
     * Fuentes de comisión que aplican a este usuario según sus entidades.
     *
     * @return string[]
     */
    public function availableSources(User $user) : array
    {
        $sources = [];

        if (Seller::where('user_id', $user->id)->exists()) {
            $sources[] = 'seller';
        }
        if (Subsidiary::where('user_id', $user->id)->exists()) {
            $sources[] = 'subsidiary';
        }
        if (Commerce::where('user_id', $user->id)->exists()) {
            $sources[] = 'network';
        }
        // Creador de salas: comercializadora o cliente API.
        if ($user->hasRole(['commerce', 'customer'])) {
            $sources[] = 'room';
        }

        return $sources;
    }

    /**
     * @param array{from?: ?string, to?: ?string, source?: ?string, status?: ?string} $filters
     *
     * @return array{rows: array<int, array<string, mixed>>, summary: array<int, array<string, mixed>>, sources: string[], range: array{from: string, to: string}, truncated: bool}
     */
    public function forUser(User $user, array $filters = []) : array
    {
        [$from, $to] = $this->range($filters);
        $sources = $this->availableSources($user);
        $wanted = in_array($filters['source'] ?? null, self::SOURCES, true) ? [$filters['source']] : $sources;

        $rows = collect();
        foreach (array_intersect($sources, $wanted) as $source) {
            $rows = $rows->concat($this->rowsFor($source, $user, $from, $to));
        }

        if (in_array($filters['status'] ?? null, ['paid', 'pending', 'refunded'], true)) {
            $rows = $rows->where('status', $filters['status']);
        }

        $rows = $rows->sortByDesc('date')->values();
        $truncated = $rows->count() > self::MAX_ROWS;

        return [
            'rows' => $rows->take(self::MAX_ROWS)->all(),
            'summary' => $this->summary($rows),
            'sources' => $sources,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'truncated' => $truncated,
        ];
    }

    private function buyerName(BingoCardLot $lot, $payment) : ?string
    {
        $external = $payment?->externalPlayer;
        if ($external) {
            return trim("{$external->first_name} {$external->last_name}") ?: null;
        }

        // Compra con billetera: el lote es del propio jugador (sin vendedor).
        if (! $lot->seller_user_id && $lot->user) {
            return trim("{$lot->user->first_name} {$lot->user->last_name}") ?: $lot->user->username;
        }

        return null;
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(array $filters) : array
    {
        $parse = fn (?string $d) => $d ? rescue(fn () => Carbon::parse($d), null, false) : null;

        $from = $parse($filters['from'] ?? null)?->startOfDay() ?? now()->startOfMonth();
        $to = $parse($filters['to'] ?? null)?->endOfDay() ?? now()->endOfDay();

        return $from->gt($to) ? [$to->copy()->startOfDay(), $from->copy()->endOfDay()] : [$from, $to];
    }

    /** @param Collection<string, mixed> $custodyPaid creator_paid_at por lot_id */
    private function row(string $source, BingoCardLot $lot, string $userId, Collection $custodyPaid) : ?array
    {
        $breakdown = (array) ($lot->financial_breakdown ?? []);
        $channel = $breakdown['channel'] ?? 'offline';

        $amount = match ($source) {
            'seller' => (float) $lot->commission_amount,
            'subsidiary' => (float) $lot->subsidiary_commission_amount,
            'network' => (float) $lot->commerce_commission_amount,
            'room' => (float) ($breakdown['room_local']['room_commission'] ?? 0),
        };

        if ($channel === 'demo') {
            return null;
        }

        $payment = $lot->payments->first();
        [$how, $status, $statusLabel] = $this->state($source, $lot, $channel, $payment?->payment_status, $userId, $custodyPaid);

        return [
            'id' => $source . '-' . $lot->id,
            'date' => $lot->created_at?->toIso8601String(),
            'source' => $source,
            'source_label' => self::SOURCE_LABELS[$source],
            'room' => $lot->room?->uuid_code,
            'buyer' => $this->buyerName($lot, $payment),
            'cards' => (int) $lot->card_count,
            'currency_id' => (string) $lot->currency_id,
            'currency' => $lot->currency?->iso_code ?? '',
            'symbol' => $lot->currency?->symbol ?? '',
            'sale_amount' => round((float) $lot->sale_amount, 2),
            'commission' => round($amount, 2),
            'how' => $how,
            'status' => $status,
            'status_label' => $statusLabel,
        ];
    }

    private function rowsFor(string $source, User $user, Carbon $from, Carbon $to) : Collection
    {
        $base = BingoCardLot::query()
            ->whereBetween('bingo_card_lots.created_at', [$from, $to])
            ->with([
                'room:id,uuid_code,creator_user_id,status_id',
                'room.status:id,code',
                'currency',
                'payments:id,card_lot_id,payment_status,external_player_id',
                'payments.externalPlayer:id,first_name,last_name',
                'user:id,username,first_name,last_name',
            ])
            ->limit(self::MAX_ROWS + 1);

        $lots = match ($source) {
            'seller' => $base->where('seller_user_id', $user->id)->get(),
            'subsidiary' => $this->whereEntity($base, 'subsidiary_id', Subsidiary::where('user_id', $user->id)->value('id'))->get(),
            'network' => $this->whereEntity($base, 'commerce_id', Commerce::where('user_id', $user->id)->value('id'))->get(),
            'room' => $base->whereHas('room', fn ($q) => $q->where('creator_user_id', $user->id))
                ->whereNotNull('financial_breakdown')->get(),
        };

        $custodyPaid = $source === 'room'
            ? PlatformReceivable::whereIn('lot_id', $lots->pluck('id'))->pluck('creator_paid_at', 'lot_id')
            : collect();

        return $lots
            ->map(fn (BingoCardLot $lot) => $this->row($source, $lot, (string) $user->id, $custodyPaid))
            ->filter(fn (?array $row) => $row !== null && $row['commission'] > 0)
            ->values();
    }

    /**
     * @param Collection<string, mixed> $custodyPaid
     *
     * @return array{0: string, 1: string, 2: string} [how, status, status_label]
     */
    private function state(string $source, BingoCardLot $lot, string $channel, ?string $paymentStatus, string $userId, Collection $custodyPaid) : array
    {
        if ($lot->status !== 'ACTIVE' || $paymentStatus === 'REJECTED') {
            return ['cash', 'refunded', 'Reembolsada'];
        }

        // Comisión de sala por compra con billetera: se acredita al cerrar la sala.
        if ($source === 'room' && $channel === 'wallet') {
            return $lot->room?->status?->code === 'FINISHED'
                ? ['wallet', 'paid', 'Acreditada en billetera']
                : ['wallet', 'pending', 'Se acredita al cerrar la sala'];
        }

        // Venta cruzada en su sala: la retuvo otro comercio y se le paga al cobrarla.
        if ($source === 'room' && (string) $lot->user_id !== $userId) {
            return $custodyPaid->get((string) $lot->id)
                ? ['custody', 'paid', 'Acreditada en billetera']
                : ['custody', 'pending', 'Se paga al cobrar la deuda con la sala cerrada'];
        }

        if ($paymentStatus === 'PENDING') {
            return ['cash', 'pending', 'Pago en revisión'];
        }

        return ['cash', 'paid', 'Cobrada en efectivo'];
    }

    /**
     * Totales por moneda (sin sumar monedas distintas), excluyendo reembolsadas.
     *
     * @param Collection<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function summary(Collection $rows) : array
    {
        $currencies = Currency::whereIn('id', $rows->pluck('currency_id')->unique())->get()->keyBy('id');

        return $rows->where('status', '!=', 'refunded')
            ->groupBy('currency_id')
            ->map(function(Collection $group, $currencyId) use ($currencies) {
                $currency = $currencies->get($currencyId);
                $fmt = fn (float $v) => trim(($currency?->symbol ?? '') . ' ' . ($currency ? $currency->formatAmount($v) : number_format($v, 2, ',', '.')));
                $total = (float) $group->sum('commission');
                $pending = (float) $group->where('status', 'pending')->sum('commission');
                $wallet = (float) $group->whereIn('how', ['wallet', 'custody'])->where('status', 'paid')->sum('commission');

                return [
                    'currency_id' => (string) $currencyId,
                    'currency' => $currency?->iso_code ?? '',
                    'count' => $group->count(),
                    'total' => $fmt($total),
                    'paid' => $fmt($total - $pending),
                    'pending' => $fmt($pending),
                    'in_wallet' => $fmt($wallet),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    private function whereEntity(Builder $query, string $column, int|string|null $id) : Builder
    {
        return $id ? $query->where($column, $id) : $query->whereRaw('1 = 0');
    }
}
