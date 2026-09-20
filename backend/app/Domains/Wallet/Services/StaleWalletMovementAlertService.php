<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Events\WalletMovementCriticalBacklog;
use App\Domains\Wallet\Models\WalletMovement;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Escalamiento de movimientos de wallet (depósitos/retiros) que llevan
 * demasiado tiempo esperando aprobación de tesorería — antes no había
 * ninguna alerta proactiva, solo visible entrando manualmente a
 * /wallet-movements.
 */
final class StaleWalletMovementAlertService
{
    private const STALE_DEPOSIT_HOURS = 6;
    /** Retiros: dinero saliendo, umbral más estricto que depósitos. */
    private const STALE_WITHDRAWAL_HOURS = 2;
    /**
     * Segundo nivel de alerta, más urgente que STALE_*_HOURS — alineado con
     * AGE_CRITICAL_HOURS que la UI ya usa en useMovementFormatters.ts::ageInfo()
     * para el badge "crítico" de la columna Antigüedad. No reemplaza el
     * aviso temprano (6h/2h por tipo): a las 24h cualquier PENDING, sin
     * distinguir tipo, ya es crítico independientemente de si es depósito o
     * retiro.
     */
    private const CRITICAL_AGE_HOURS = 24;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Notifica a los usuarios con permiso wallet.movements.manage (mismo
     * permiso que protege WalletMovementController::approve(), ver
     * routes/modules/wallets.php) el conteo de movimientos pendientes
     * atrasados. Sin columna de dedupe: se notifica en cada corrida
     * mientras sigan pendientes — cadencia de 15 min, mismo criterio que
     * logs:alert-threshold.
     *
     * @return int cantidad de movimientos atrasados encontrados
     */
    public function notifyStalePending() : int
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
                    'stale_deposits' => $staleDeposits,
                    'url' => '/wallet-movements',
                ],
            );
        }

        return $staleTotal;
    }

    /**
     * Segundo nivel de alerta: PENDING (cualquier tipo) con más de
     * CRITICAL_AGE_HOURS (24h) de antigüedad. A diferencia de
     * notifyStalePending(), no distingue depósito/retiro — a las 24h ambos
     * son igual de urgentes. Notifica in-app (mismo público) y además
     * dispara un broadcast al canal admins.wallet-movements para reflejo en
     * tiempo real sin depender de que el admin abra el centro de
     * notificaciones.
     *
     * @return int cantidad de movimientos críticos encontrados
     */
    public function notifyCriticalPending() : int
    {
        $criticalCount = WalletMovement::where('status', WalletMovement::STATUS_PENDING)
            ->where('created_at', '<=', now()->subHours(self::CRITICAL_AGE_HOURS))
            ->count();

        if ($criticalCount === 0) {
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
                title: '🔴 Movimientos CRÍTICOS sin resolver',
                message: "{$criticalCount} movimiento(s) llevan más de " . self::CRITICAL_AGE_HOURS . "h pendientes de aprobación. Revisión urgente requerida.",
                data: [
                    'critical_count' => $criticalCount,
                    'url' => '/wallet-movements',
                ],
            );
        }

        // Best-effort — nunca bloquea la notificación in-app si Reverb no
        // responde, mismo patrón try/catch que el resto de broadcasts de wallet.
        try {
            broadcast(new WalletMovementCriticalBacklog($criticalCount));
        } catch (Throwable $e) {
            Log::warning('[StaleWalletMovementAlertService] broadcast de critical-backlog falló: ' . $e->getMessage());
        }

        return $criticalCount;
    }
}
