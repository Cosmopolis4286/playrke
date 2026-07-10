<?php
declare(strict_types=1);
namespace App\Domains\Games\Bingo\Services\Winner;

/**
 * Política de reintentos para pagos de premios de bingo.
 * Centraliza MAX_ATTEMPTS y criterios de back-off para que
 * RetryFailedBingoPrizePayoutsCommand y cualquier retry futuro
 * usen los mismos parámetros.
 */
final class BingoPrizePayoutRetryPolicy
{
    public const MAX_ATTEMPTS = 5;

    /**
     * Segundos de espera antes del siguiente intento (back-off lineal: intento × 60s).
     * Intento 1→60s, 2→120s, 3→180s, 4→240s, 5→300s.
     */
    public static function backoffSeconds(int $attempts): int
    {
        return $attempts * 60;
    }

    public static function shouldAbandon(int $attempts): bool
    {
        return $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * ¿Hay que esperar todavía? Compara next_attempt_at con ahora.
     */
    public static function isReadyToRetry(?\DateTimeInterface $lastAttemptedAt, int $attempts): bool
    {
        if ($lastAttemptedAt === null) {
            return true;
        }
        $nextAt = (clone $lastAttemptedAt)->modify(
            sprintf('+%d seconds', self::backoffSeconds($attempts))
        );
        return $nextAt <= new \DateTimeImmutable();
    }
}
