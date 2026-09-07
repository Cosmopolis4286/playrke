<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\WalletMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Detecta patrones sospechosos en operaciones de wallet.
 *
 * Checks implementados
 * ────────────────────
 * 1. Velocity diario: no más de N depósitos/retiros por día por usuario.
 * 2. Duplicate reference: mismo número de referencia en las últimas 24 h.
 * 3. Duplicate amount+window: mismo monto en < X segundos (double-submit).
 *
 * Todos los métodos lanzan DomainException si detectan fraude,
 * por lo que se integran con un try/catch en WalletService.
 */
final class WalletFraudService
{
    // Ventana anti double-submit (segundos)
    private const DOUBLE_SUBMIT_WINDOW_SECONDS = 30;
    // Máximo de operaciones de cada tipo por día por usuario
    private const MAX_DEPOSITS_PER_DAY = 10;
    private const MAX_WITHDRAWALS_PER_DAY = 5;

    /**
     * Verifica velocity de depósitos.
     */
    public function checkDepositVelocity(string $userId) : void
    {
        $count = $this->operationCountToday($userId, WalletMovement::TYPE_DEPOSIT);

        if ($count >= self::MAX_DEPOSITS_PER_DAY) {
            Log::warning('[Fraud] Velocity depósito excedida', ['user_id' => $userId, 'count' => $count]);

            throw WalletDomainException::depositVelocityExceeded();
        }
    }

    /**
     * Previene double-submit: mismo monto en la misma wallet en < 30 s.
     */
    public function checkDoubleSubmit(string $walletId, float $amount, string $type) : void
    {
        $key = "fraud:ds:{$walletId}:{$type}:" . (int) $amount;

        if (Cache::has($key)) {
            Log::warning('[Fraud] Double-submit detectado', [
                'wallet_id' => $walletId,
                'amount' => $amount,
                'type' => $type,
            ]);

            throw WalletDomainException::duplicateSubmission();
        }

        Cache::put($key, true, self::DOUBLE_SUBMIT_WINDOW_SECONDS);
    }

    /**
     * Verifica que el número de referencia no se haya usado en las últimas 24 h.
     */
    public function checkDuplicateReference(string $walletId, string $reference) : void
    {
        if (empty(trim($reference))) {
            return;
        }

        $exists = WalletMovement::where('wallet_id', $walletId)
            ->where('reference_id', $reference)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->exists();

        if ($exists) {
            Log::warning('[Fraud] Referencia duplicada', ['wallet_id' => $walletId, 'reference' => $reference]);

            throw WalletDomainException::duplicateReference();
        }
    }

    /**
     * Verifica velocity de retiros.
     */
    public function checkWithdrawalVelocity(string $userId) : void
    {
        $count = $this->operationCountToday($userId, WalletMovement::TYPE_WITHDRAWAL);

        if ($count >= self::MAX_WITHDRAWALS_PER_DAY) {
            Log::warning('[Fraud] Velocity retiro excedida', ['user_id' => $userId, 'count' => $count]);

            throw WalletDomainException::withdrawalVelocityExceeded();
        }
    }

    private function operationCountToday(string $userId, string $type) : int
    {
        return (int) Cache::remember(
            $this->velocityCacheKey($userId, $type),
            Carbon::now()->secondsUntilEndOfDay() + 1,
            fn () => WalletMovement::where('user_id', $userId)
                ->where('type', $type)
                ->where('created_at', '>=', Carbon::today())
                ->count(),
        );
    }

    /**
     * Invalida el contador de velocity del día tras registrar una operación
     * nueva. Sin esto, Cache::remember() en operationCountToday() congela el
     * conteo en el primer valor calculado en el día (a menudo 0, si el
     * primer check ocurre antes de la primera operación) y checkDeposit/
     * WithdrawalVelocity() nunca vuelve a bloquear pese a MAX_*_PER_DAY —
     * el límite de velocity queda evadible el resto del día.
     */
    public function invalidateVelocity(string $userId, string $type) : void
    {
        Cache::forget($this->velocityCacheKey($userId, $type));
    }

    private function velocityCacheKey(string $userId, string $type) : string
    {
        return "fraud:vel:{$userId}:{$type}:" . now()->format('Y-m-d');
    }
}
