<?php

namespace App\Services\Platform;

use App\Models\Platform\PlatformReceivable;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cuentas por cobrar de la plataforma (comisión RKE de ventas fuera del sistema).
 *
 * En una venta con comprobante (vendedor → jugador externo) o cobrada en mano
 * por el comercio, el dinero nunca pasa por una wallet: lo tiene el comercio.
 * Antes la comisión RKE igual se acreditaba a la wallet de plataforma, que
 * mostraba un saldo que en realidad no tenía. Ahora:
 *  - al vender se registra la deuda (record) — no se acredita nada;
 *  - si el lote se reembolsa, la deuda se anula (cancelForLot);
 *  - cuando el comercio paga, un superadmin la marca cobrada (settle) y recién
 *    ahí se acredita el RKE a la wallet de plataforma.
 */
final class PlatformReceivableService
{
    public function __construct(
        private readonly PlatformWalletService $platformWallet,
    ) {}

    /** Idempotente por lote (lot_id único). */
    public function record(string $debtorUserId, string $currencyId, float $amount, ?string $roomId, ?string $lotId, ?string $notes = null): ?PlatformReceivable
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }
        if ($lotId !== null && ($existing = PlatformReceivable::where('lot_id', $lotId)->first())) {
            return $existing;
        }

        return PlatformReceivable::create([
            'source'         => PlatformReceivable::SOURCE_BINGO_RKE,
            'debtor_user_id' => $debtorUserId,
            'currency_id'    => $currencyId,
            'room_id'        => $roomId,
            'lot_id'         => $lotId,
            'amount'         => $amount,
            'status'         => PlatformReceivable::STATUS_PENDING,
            'notes'          => $notes,
        ]);
    }

    /**
     * Lote reembolsado: su deuda ya no aplica. Si ya estaba cobrada, se
     * devuelve false para que el caller revierta el RKE acreditado.
     */
    public function cancelForLot(string $lotId): bool
    {
        $receivable = PlatformReceivable::where('lot_id', $lotId)->lockForUpdate()->first();
        if (!$receivable || $receivable->status === PlatformReceivable::STATUS_CANCELLED) {
            return true;
        }
        if ($receivable->status === PlatformReceivable::STATUS_SETTLED) {
            return false;
        }
        $receivable->update(['status' => PlatformReceivable::STATUS_CANCELLED, 'cancelled_at' => now()]);
        return true;
    }

    /**
     * Marca cobradas todas las deudas pendientes de un comercio en una moneda y
     * acredita el total a la wallet de plataforma.
     *
     * @return array{count: int, amount: float}
     */
    public function settle(string $debtorUserId, string $currencyId, string $settledBy): array
    {
        return DB::transaction(function () use ($debtorUserId, $currencyId, $settledBy) {
            $pending = PlatformReceivable::where('debtor_user_id', $debtorUserId)
                ->where('currency_id', $currencyId)
                ->where('status', PlatformReceivable::STATUS_PENDING)
                ->lockForUpdate()
                ->get();

            if ($pending->isEmpty()) {
                return ['count' => 0, 'amount' => 0.0];
            }

            $total = round((float) $pending->sum('amount'), 2);

            PlatformReceivable::whereIn('id', $pending->pluck('id'))->update([
                'status'     => PlatformReceivable::STATUS_SETTLED,
                'settled_at' => now(),
                'settled_by' => $settledBy,
            ]);

            $debtorName = User::where('id', $debtorUserId)->value('username') ?? $debtorUserId;
            $this->platformWallet->creditRke($currencyId, $total, "Cobro RKE – {$debtorName} ({$pending->count()} venta(s))");

            return ['count' => $pending->count(), 'amount' => $total];
        });
    }

    /**
     * Deudas pendientes agrupadas por comercio y moneda (panel de Conciliación).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendingSummary(?string $debtorUserId = null): array
    {
        return PlatformReceivable::query()
            ->where('platform_receivables.status', PlatformReceivable::STATUS_PENDING)
            ->when($debtorUserId, fn ($q) => $q->where('debtor_user_id', $debtorUserId))
            ->join('users', 'users.id', '=', 'platform_receivables.debtor_user_id')
            ->join('currencies', 'currencies.id', '=', 'platform_receivables.currency_id')
            ->groupBy('debtor_user_id', 'platform_receivables.currency_id', 'users.username', 'currencies.iso_code', 'currencies.symbol')
            ->selectRaw('debtor_user_id, platform_receivables.currency_id, users.username as debtor, currencies.iso_code as currency, currencies.symbol, COUNT(*) as sales, SUM(amount) as total, MIN(platform_receivables.created_at) as oldest')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'debtor_user_id' => (string) $r->debtor_user_id,
                'debtor'         => $r->debtor,
                'currency_id'    => (string) $r->currency_id,
                'currency'       => $r->currency,
                'symbol'         => $r->symbol,
                'sales'          => (int) $r->sales,
                'total'          => round((float) $r->total, 2),
                'oldest'         => $r->oldest,
            ])
            ->all();
    }
}
