<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

/**
 * Checks whether an advance request still targets the active question.
 *
 * Timeout jobs and answer requests may arrive after another request has
 * already advanced the game. Such stale requests must never advance again.
 */
final class TriviaQuestionAdvanceGuard
{
    public static function matchesExpectedQuestion(string $expectedQuestionId, ?array $currentQuestion) : bool
    {
        if ($currentQuestion === null) {
            return false;
        }

        $currentQuestionId = $currentQuestion['question_id'] ?? $currentQuestion['id'] ?? null;

        return is_string($currentQuestionId) || is_int($currentQuestionId)
            ? (string) $currentQuestionId === $expectedQuestionId
            : false;
    }
}
