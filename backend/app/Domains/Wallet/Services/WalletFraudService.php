<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\WalletMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Detecta patrones sospechosos en operaciones de wallet.
 *
 * Checks implementados
 * ────────────────────
 * 1. Velocity diario: no más de N depósitos/retiros por día por usuario.
 * 2. Duplicate reference: mismo número de referencia en las últimas 24 h.
 * 3. Duplicate amount+window: mismo monto en < X segundos (double-submit).
 * 4. Lockout del PIN de retiro: mismo patrón que LoginRequest::
 *    ensureIsNotRateLimited() (RateLimiter::hit/tooManyAttempts/clear), por
 *    userId en vez de userId+ip porque el PIN se valida post-login (la IP ya
 *    no discrimina el mismo riesgo que discrimina en el login).
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
    // Intentos de PIN de retiro antes de bloquear, y minutos de bloqueo —
    // mismo umbral que LoginRequest (5), pero con una ventana de decay más
    // larga porque un PIN de 6 dígitos tiene mucho menos entropía que un
    // password: 5 intentos por minuto sería fuerza-bruteable en horas.
    private const WITHDRAWAL_PIN_MAX_ATTEMPTS = 5;
    private const WITHDRAWAL_PIN_DECAY_MINUTES = 15;

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
        // number_format a 2 decimales fijos (no (int) $amount, que truncaba
        // la parte decimal — 10.10 y 10.90 compartían la misma key y un
        // segundo depósito/retiro con monto distinto pero mismo entero-base
        // se bloqueaba como "duplicado" sin serlo, un falso positivo).
        $key = "fraud:ds:{$walletId}:{$type}:" . number_format($amount, 2, '.', '');

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

    // ─────────────────────────────────────────────────────────────────
    // Lockout del PIN de retiro (RateLimiter — mismo patrón que LoginRequest)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Lanza WalletDomainException si el usuario ya agotó
     * WITHDRAWAL_PIN_MAX_ATTEMPTS intentos fallidos de PIN en la ventana de
     * WITHDRAWAL_PIN_DECAY_MINUTES. Debe llamarse ANTES de comparar el PIN
     * recibido contra el hash — nunca dejar que un intento adicional
     * siquiera llegue a Hash::check() una vez bloqueado.
     */
    public function checkWithdrawalPinLockout(string $userId) : void
    {
        $key = $this->withdrawalPinKey($userId);

        if (! RateLimiter::tooManyAttempts($key, self::WITHDRAWAL_PIN_MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        Log::warning('[Fraud] PIN de retiro bloqueado por intentos fallidos', [
            'user_id'          => $userId,
            'available_in_sec' => $seconds,
        ]);

        throw WalletDomainException::withdrawalPinLocked((int) ceil($seconds / 60));
    }

    /**
     * Registra un intento fallido de PIN. Devuelve los intentos restantes
     * antes del bloqueo, para que el mensaje de error pueda avisar
     * "te quedan N intentos" sin exponer el umbral exacto de forma que
     * facilite calibrar un ataque (se muestra solo cuando faltan pocos).
     */
    public function recordWithdrawalPinFailure(string $userId) : int
    {
        $key = $this->withdrawalPinKey($userId);

        RateLimiter::hit($key, self::WITHDRAWAL_PIN_DECAY_MINUTES * 60);

        $remaining = max(0, self::WITHDRAWAL_PIN_MAX_ATTEMPTS - RateLimiter::attempts($key));

        Log::warning('[Fraud] Intento fallido de PIN de retiro', [
            'user_id'   => $userId,
            'remaining' => $remaining,
        ]);

        return $remaining;
    }

    /**
     * Limpia el contador de intentos fallidos tras una validación exitosa
     * del PIN — mismo momento que RateLimiter::clear() en LoginRequest tras
     * un login correcto.
     */
    public function clearWithdrawalPinAttempts(string $userId) : void
    {
        RateLimiter::clear($this->withdrawalPinKey($userId));
    }

    private function withdrawalPinKey(string $userId) : string
    {
        return "withdrawal-pin:{$userId}";
    }
}
