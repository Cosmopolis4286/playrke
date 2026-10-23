<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Games\Trivia;

use App\Domains\Games\Trivia\Services\TriviaQuestionAdvanceGuard;
use PHPUnit\Framework\TestCase;

final class TriviaQuestionAdvanceGuardTest extends TestCase
{
    public function test_matching_active_question_can_advance(): void
    {
        self::assertTrue(TriviaQuestionAdvanceGuard::matchesExpectedQuestion(
            'question-123',
            ['question_id' => 'question-123'],
        ));
    }

    public function test_stale_question_cannot_advance_after_active_question_changes(): void
    {
        self::assertFalse(TriviaQuestionAdvanceGuard::matchesExpectedQuestion(
            'question-old',
            ['question_id' => 'question-new'],
        ));
    }

    public function test_missing_active_question_cannot_advance(): void
    {
        self::assertFalse(TriviaQuestionAdvanceGuard::matchesExpectedQuestion('question-123', null));
    }

    public function test_legacy_id_field_is_supported(): void
    {
        self::assertTrue(TriviaQuestionAdvanceGuard::matchesExpectedQuestion(
            '123',
            ['id' => 123],
        ));
    }

    public function test_missing_question_identifier_cannot_advance(): void
    {
        self::assertFalse(TriviaQuestionAdvanceGuard::matchesExpectedQuestion(
            'question-123',
            ['server_timestamp_ms' => 123456],
        ));
    }
}
