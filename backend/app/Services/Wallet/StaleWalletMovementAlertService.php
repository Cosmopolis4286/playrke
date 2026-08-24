<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\WalletMovement;
use App\Services\NotificationService;

/**
 * Escalamiento de movimientos de wallet (depósitos/retiros) que llevan
 * demasiado tiempo esperando aprobación de tesorería — antes no había
 * ninguna alerta proactiva, solo visible entrando manualmente a
 * /wallet-movements.
 */
final class StaleWalletMovementAlertService
{
    /** Retiros: dinero saliendo, umbral más estricto que depósitos. */
    private const STALE_WITHDRAWAL_HOURS = 2;
    private const STALE_DEPOSIT_HOURS    = 6;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Notifica a los usuarios con permiso wallet.movements.manage (mismo
     * permiso que protege WalletMovementController::approve(), ver
     * routes/modules/wallets.php) el conteo de movimientos pendientes
     * atrasados. Sin columna de dedupe: se notifica en cada corrida
     * mientras sigan pendientes — cadencia de 15 min, mismo criterio que
     * logs:alert-threshold.
     *
     * @return int Cantidad de movimientos atrasados encontrados.
     */
    public function notifyStalePending(): int
    {
        $staleWithdrawals = WalletMovement::where('status', WalletMovement::STATUS_PENDING)
            ->where('type', WalletMovement::TYPE_WITHDRAWAL)
            ->where('created_at', '<=', now()->subHours(self::STALE_WITHDRAWAL_HOURS))
            ->count();

        $staleDeposits = WalletMovement::where('status', WalletMovement::STATUS_PENDING)
            ->where('type', WalletMovement::TYPE_DEPOSIT)
            ->where('created_at', '<=', now()->subHours(self::STALE_DEPOSIT_HOURS))
            ->count();

        $staleTotal = $staleWithdrawals + $staleDeposits;

        if ($staleTotal === 0) {
            return 0;
        }

        $approverIds = User::permission('wallet.movements.manage')
            ->where('status', 'ACTIVE')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        foreach ($approverIds as $approverId) {
            $this->notificationService->create(
                userId: $approverId,
                type: 'SYSTEM',
                title: '⏳ Movimientos de billetera atrasados',
                message: "{$staleWithdrawals} retiro(s) y {$staleDeposits} depósito(s) llevan más tiempo del esperado esperando aprobación.",
                data: [
                    'stale_withdrawals' => $staleWithdrawals,
                    'stale_deposits'    => $staleDeposits,
                    'url'               => '/wallet-movements',
                ],
            );
        }

        return $staleTotal;
    }
}
