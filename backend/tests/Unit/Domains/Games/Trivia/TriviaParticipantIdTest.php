<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Games\Trivia;

use App\Models\Games\Trivia\TriviaParticipant;
use PHPUnit\Framework\TestCase;

final class TriviaParticipantIdTest extends TestCase
{
    public function test_generated_participant_ids_are_unique() : void
    {
        $ids = [];

        for ($i = 0; $i < 100; $i++) {
            $ids[] = TriviaParticipant::generateSnowflakeId();
        }

        $this->assertCount(100, array_unique($ids));
    }

    public function test_generated_participant_ids_fit_the_trivia_schema() : void
    {
        $id = TriviaParticipant::generateSnowflakeId();

        $this->assertMatchesRegularExpression('/^\d+$/', $id);
        $this->assertLessThanOrEqual(20, strlen($id));
    }
}
