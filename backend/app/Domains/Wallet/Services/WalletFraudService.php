<?php

namespace App\Domains\Wallet\Services;

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
    // Máximo de operaciones de cada tipo por día por usuario
    private const MAX_DEPOSITS_PER_DAY    = 10;
    private const MAX_WITHDRAWALS_PER_DAY = 5;

    // Ventana anti double-submit (segundos)
    private const DOUBLE_SUBMIT_WINDOW_SECONDS = 30;

    /**
     * Verifica velocity de depósitos.
     */
    public function checkDepositVelocity(string $userId): void
    {
        $count = $this->operationCountToday($userId, WalletMovement::TYPE_DEPOSIT);

        if ($count >= self::MAX_DEPOSITS_PER_DAY) {
            Log::warning('[Fraud] Velocity depósito excedida', ['user_id' => $userId, 'count' => $count]);
            throw new \DomainException('Has alcanzado el límite de depósitos por hoy. Inténtalo mañana.');
        }
    }

    /**
     * Verifica velocity de retiros.
     */
    public function checkWithdrawalVelocity(string $userId): void
    {
        $count = $this->operationCountToday($userId, WalletMovement::TYPE_WITHDRAWAL);

        if ($count >= self::MAX_WITHDRAWALS_PER_DAY) {
            Log::warning('[Fraud] Velocity retiro excedida', ['user_id' => $userId, 'count' => $count]);
            throw new \DomainException('Has alcanzado el límite de retiros por hoy. Inténtalo mañana.');
        }
    }

    /**
     * Verifica que el número de referencia no se haya usado en las últimas 24 h.
     */
    public function checkDuplicateReference(string $walletId, string $reference): void
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
            throw new \DomainException('Este número de referencia ya fue utilizado recientemente. Verifica el comprobante.');
        }
    }

    /**
     * Previene double-submit: mismo monto en la misma wallet en < 30 s.
     */
    public function checkDoubleSubmit(string $walletId, float $amount, string $type): void
    {
        $key = "fraud:ds:{$walletId}:{$type}:" . (int) $amount;

        if (Cache::has($key)) {
            Log::warning('[Fraud] Double-submit detectado', [
                'wallet_id' => $walletId,
                'amount'    => $amount,
                'type'      => $type,
            ]);
            throw new \DomainException('Solicitud duplicada. Espera unos segundos antes de reintentar.');
        }

        Cache::put($key, true, self::DOUBLE_SUBMIT_WINDOW_SECONDS);
    }

    private function operationCountToday(string $userId, string $type): int
    {
        $cacheKey = "fraud:vel:{$userId}:{$type}:" . now()->format('Y-m-d');
        // TTL al fin del día — la clave ya incluye la fecha, por lo que no hay
        // riesgo de rollover: la del día siguiente tiene clave distinta.
        $ttl = Carbon::now()->secondsUntilEndOfDay() + 1;

        return (int) Cache::remember($cacheKey, $ttl, fn () =>
            WalletMovement::where('user_id', $userId)
                ->where('type', $type)
                ->where('created_at', '>=', Carbon::today())
                ->count()
        );
    }
}
