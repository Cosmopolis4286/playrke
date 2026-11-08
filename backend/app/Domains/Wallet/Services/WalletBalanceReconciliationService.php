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
    public function findDrifted() : Collection
    {
        // Movimiento cuyo balance_after se escribió último, por wallet. No es
        // siempre el de mayor id: aprobar un depósito (o un movimiento
        // genérico) reescribe balance_after del movimiento PENDING original en
        // el momento de aprobarlo (processed_at), aunque después de crearlo
        // haya habido otros movimientos. Comparar contra el último por id daba
        // drift falso cada vez que se aprobaba un depósito con compras en el
        // medio. Los retiros aprobados NO reescriben balance_after (el saldo
        // ya se descontó al solicitarse): su foto es la de created_at.
        // Empates en el mismo segundo: por id (Snowflake, monótono creciente).
        $snapshotAt = "CASE WHEN status = 'COMPLETED' AND type <> '" . WalletMovement::TYPE_WITHDRAWAL . "' AND processed_at IS NOT NULL AND processed_at > created_at THEN processed_at ELSE created_at END";

        $ranked = WalletMovement::query()
            ->selectRaw("id, ROW_NUMBER() OVER (PARTITION BY wallet_id ORDER BY {$snapshotAt} DESC, id DESC) AS rn");

        $latestMovementIds = DB::query()
            ->fromSub($ranked, 'ranked')
            ->where('rn', 1)
            ->pluck('id');

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
            $actual = round((float) $wallet->balance, 2);

            if ($expected !== $actual) {
                $drifted->push([
                    'wallet_id' => (string) $wallet->id,
                    'user_id' => (string) $wallet->user_id,
                    'wallet_balance' => $actual,
                    'expected_balance' => $expected,
                    'last_movement_id' => (string) $movement->id,
                    'last_movement_at' => $movement->created_at?->toIso8601String() ?? '',
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
    public function reconcileAndAlert() : int
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
            fn (array $d) => "wallet {$d['wallet_id']}: balance={$d['wallet_balance']} esperado={$d['expected_balance']}",
        )->implode('; ');

        foreach ($approverIds as $approverId) {
            $this->notificationService->create(
                userId: $approverId,
                type: 'SYSTEM',
                title: '🚨 Drift de balance detectado',
                message: "{$drifted->count()} wallet(s) con balance desincronizado del ledger. {$summary}",
                data: [
                    'drifted_count' => $drifted->count(),
                    'wallets' => $drifted->all(),
                    'url' => '/wallet-movements',
                ],
            );
        }

        return $drifted->count();
    }
}
