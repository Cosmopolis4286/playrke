<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Detecta (nunca auto-corrige) drift entre wallets.balance y el ledger de
 * wallet_movements — hueco confirmado en la auditoría de flujo de caja: no
 * existía ningún mecanismo de verificación cruzada entre ambos, así que un
 * bug futuro (o una migración de datos manual) podía desincronizarlos sin
 * que nadie se enterara hasta que un usuario reportara una discrepancia.
 *
 * Invariante verificado: balance_after del WalletMovement MÁS RECIENTE de
 * cada wallet == wallets.balance actual. Es más simple y confiable que
 * sumar SUM(amount con signo) porque cada mutación real de balance (en
 * WalletService/PlatformWalletService, confirmado exhaustivamente: todo
 * increment/decrementBalance() ocurre dentro de la misma transacción que
 * crea su WalletMovement) ya calcula y persiste balance_after inmediatamente
 * después de la mutación — no hay que reconstruir el signo de cada tipo vía
 * MovementType::is_credit ni reinterpretar splits bono/balance.
 *
 * LIMITACIÓN CONOCIDA: solo cubre `balance` (el campo retirable, con
 * impacto financiero directo). bonus_balance no tiene un campo dedicado
 * equivalente a balance_after — queda registrado solo dentro de
 * metadata.bonus_balance_after en los movimientos que hacen split
 * bono/balance, y no todos los que tocan bonus_balance lo registran ahí, así
 * que reconciliarlo con la misma técnica daría falsos positivos. Mismo
 * motivo para pending_debt. Ampliar esto requeriría antes normalizar esos
 * dos campos a columnas propias de WalletMovement.
 */
final class WalletBalanceReconciliationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * @return Collection<int, array{wallet_id: string, user_id: string, wallet_balance: float, expected_balance: float, last_movement_id: string, last_movement_at: string}>
     */
    public function findDrifted(): Collection
    {
        // Último movimiento por wallet_id en una sola query (evita N+1 de
        // "por cada wallet, buscar su último movimiento"). created_at puede
        // empatar entre dos movimientos de la misma wallet insertados en el
        // mismo segundo — se desempata por id (Snowflake, monótono
        // creciente), mismo criterio que CommandService::notifyIfNewFailure().
        $latestMovementIds = WalletMovement::query()
            ->selectRaw('wallet_id, MAX(id) as latest_id')
            ->groupBy('wallet_id')
            ->pluck('latest_id');

        // La agregación anterior por MAX(id) asume que un id mayor siempre
        // es más reciente en created_at — cierto para Snowflake IDs
        // (monótonos crecientes por timestamp de generación), pero NO
        // necesariamente el mismo id que MAX(created_at) devolvería si
        // hubiera relojes desincronizados entre workers. Confirmar el orden
        // real con un segundo pase por wallet sería más robusto, pero
        // encarece la query sin un caso conocido de reloj desincronizado en
        // este proyecto (un solo servidor de aplicación).
        $latestMovements = WalletMovement::whereIn('id', $latestMovementIds)
            ->get(['id', 'wallet_id', 'user_id', 'balance_after', 'created_at']);

        if ($latestMovements->isEmpty()) {
            return collect();
        }

        $wallets = Wallet::whereIn('id', $latestMovements->pluck('wallet_id'))
            ->get(['id', 'user_id', 'balance'])
            ->keyBy('id');

        $drifted = collect();

        foreach ($latestMovements as $movement) {
            $wallet = $wallets->get($movement->wallet_id);
            if (! $wallet) {
                continue;
            }

            $expected = round((float) $movement->balance_after, 2);
            $actual   = round((float) $wallet->balance, 2);

            if ($expected !== $actual) {
                $drifted->push([
                    'wallet_id'         => (string) $wallet->id,
                    'user_id'           => (string) $wallet->user_id,
                    'wallet_balance'    => $actual,
                    'expected_balance'  => $expected,
                    'last_movement_id'  => (string) $movement->id,
                    'last_movement_at'  => $movement->created_at?->toIso8601String() ?? '',
                ]);
            }
        }

        return $drifted;
    }

    /**
     * Notifica a los usuarios con permiso wallet.movements.manage (mismo
     * permiso que aprueba/rechaza movimientos — quien ya gestiona dinero
     * real es el destinatario correcto de esta alerta) el detalle de cada
     * wallet con drift. Sin dedupe: se re-alerta mientras el drift no se
     * corrija manualmente, mismo criterio que StaleWalletMovementAlertService.
     *
     * @return int cantidad de wallets con drift detectado
     */
    public function reconcileAndAlert(): int
    {
        $drifted = $this->findDrifted();

        if ($drifted->isEmpty()) {
            return 0;
        }

        $approverIds = User::permission('wallet.movements.manage')
            ->where('status', 'ACTIVE')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $summary = $drifted->take(10)->map(
            fn (array $d) => "wallet {$d['wallet_id']}: balance={$d['wallet_balance']} esperado={$d['expected_balance']}"
        )->implode('; ');

        foreach ($approverIds as $approverId) {
            $this->notificationService->create(
                userId: $approverId,
                type: 'SYSTEM',
                title: '🚨 Drift de balance detectado',
                message: "{$drifted->count()} wallet(s) con balance desincronizado del ledger. {$summary}",
                data: [
                    'drifted_count' => $drifted->count(),
                    'wallets'       => $drifted->all(),
                    'url'           => '/wallet-movements',
                ],
            );
        }

        return $drifted->count();
    }
}
