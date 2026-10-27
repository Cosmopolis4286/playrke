<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use InvalidArgumentException;

/**
 * Server-authoritative timing for a Trivia answer.
 *
 * The elapsed time is kept separate from the capped response time so that
 * responses arriving after the deadline cannot be mistaken for on-time ones.
 */
final class TriviaAnswerTiming
{
    /**
     * @return array{elapsed_ms: int, response_time_ms: int, is_late: bool}
     */
    public static function calculate(
        float $serverTimestampMs,
        float $nowMs,
        int $effectiveTimeLimitMs,
    ) : array {
        if (! is_finite($serverTimestampMs) || $serverTimestampMs <= 0) {
            throw new InvalidArgumentException('A valid server timestamp is required.');
        }

        if (! is_finite($nowMs) || $nowMs < $serverTimestampMs) {
            throw new InvalidArgumentException('The current timestamp must not precede the server timestamp.');
        }

        if ($effectiveTimeLimitMs <= 0) {
            throw new InvalidArgumentException('The effective time limit must be positive.');
        }

        $elapsedMs = (int) floor($nowMs - $serverTimestampMs);

        return [
            'elapsed_ms' => $elapsedMs,
            'response_time_ms' => min($elapsedMs, $effectiveTimeLimitMs),
            'is_late' => $elapsedMs > $effectiveTimeLimitMs,
        ];
    }
}
