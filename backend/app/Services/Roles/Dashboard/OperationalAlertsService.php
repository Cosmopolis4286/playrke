<?php

namespace App\Services\Roles\Dashboard;

use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Services\Menu\SidebarMenuService;

/**
 * Alertas operativas de dinero para Admin y SuperAdmin: lo que, si nadie lo
 * atiende, deja a un comprador sin cartones, sin reembolso o sin premio.
 * La salud técnica (jobs, Reverb, comandos) sigue en
 * SuperAdminDashboardContextService::buildSystemHealth().
 */
final class OperationalAlertsService
{
    /** Horas en revisión a partir de las cuales un pago se considera atrasado. */
    public const STALE_PAYMENT_HOURS = 2;

    public function __construct(
        private readonly SidebarMenuService $sidebarMenu,
    ) {
    }

    /**
     * Solo las alertas con algo pendiente, de mayor a menor severidad.
     *
     * @param bool $includeWalletMovements el superadmin ya ve los movimientos
     *                                     pendientes en su propio panel
     *
     * @return array<int, array{code: string, label: string, count: int, severity: 'critical'|'warning', href: ?string, action_label: ?string}>
     */
    public function alerts(bool $includeWalletMovements = true) : array
    {
        $alerts = [];

        $pendingPayments = BingoCardPayment::where('payment_status', 'PENDING')->count();
        if ($pendingPayments > 0) {
            $stale = BingoCardPayment::where('payment_status', 'PENDING')
                ->where('created_at', '<', now()->subHours(self::STALE_PAYMENT_HOURS))
                ->count();

            $alerts[] = $this->alert(
                'payments_pending',
                $stale > 0
                    ? "Pagos de cartones por revisar ({$stale} con más de " . self::STALE_PAYMENT_HOURS . ' h)'
                    : 'Pagos de cartones por revisar',
                $pendingPayments,
                $stale > 0 ? 'critical' : 'warning',
                '/bingo/card-payments',
                'Revisar pagos',
            );
        }

        $refunds = BingoLotRefundFailure::whereIn('status', ['PENDING', 'ABANDONED'])
            ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN status = 'ABANDONED' THEN 1 ELSE 0 END) AS abandoned")
            ->toBase()
            ->first();
        if ((int) ($refunds->total ?? 0) > 0) {
            $abandoned = (int) $refunds->abandoned;
            $alerts[] = $this->alert(
                'refund_failures',
                $abandoned > 0
                    ? "Reembolsos fallidos ({$abandoned} abandonados, requieren acción manual)"
                    : 'Reembolsos fallidos en reintento automático',
                (int) $refunds->total,
                $abandoned > 0 ? 'critical' : 'warning',
                '/bingo/card-payments',
                'Ver reembolsos',
            );
        }

        $stuckPrizes = BingoCardPrize::whereIn('prize_status', ['disputed', 'suspended'])->count();
        if ($stuckPrizes > 0) {
            $alerts[] = $this->alert('stuck_prizes', 'Premios en disputa o suspendidos', $stuckPrizes, 'warning', '/bingo/winners', 'Ver ganadores');
        }

        if ($includeWalletMovements) {
            $movements = $this->sidebarMenu->pendingWalletMovementsCount();
            if ($movements > 0) {
                $alerts[] = $this->alert('wallet_movements', 'Recargas y retiros por aprobar', $movements, 'warning', '/wallet-movements', 'Ver movimientos');
            }
        }

        usort($alerts, fn (array $a, array $b) => ($a['severity'] === 'critical' ? 0 : 1) <=> ($b['severity'] === 'critical' ? 0 : 1));

        return $alerts;
    }

    /** @return array{code: string, label: string, count: int, severity: 'critical'|'warning', href: ?string, action_label: ?string} */
    private function alert(string $code, string $label, int $count, string $severity, ?string $href, ?string $actionLabel) : array
    {
        return [
            'code' => $code,
            'label' => $label,
            'count' => $count,
            'severity' => $severity,
            'href' => $href,
            'action_label' => $actionLabel,
        ];
    }
}
