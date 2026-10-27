<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Games\Trivia;

use App\Domains\Games\Trivia\Services\TriviaAnswerTiming;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TriviaAnswerTimingTest extends TestCase
{
    public function test_extra_time_is_respected_as_the_effective_deadline() : void
    {
        $timing = TriviaAnswerTiming::calculate(1000.0, 15000.0, 15000);

        $this->assertSame(14000, $timing['elapsed_ms']);
        $this->assertFalse($timing['is_late']);
    }

    public function test_future_server_timestamp_is_rejected() : void
    {
        $this->expectException(InvalidArgumentException::class);

        TriviaAnswerTiming::calculate(6000.0, 5000.0, 10000);
    }

    public function test_missing_server_timestamp_is_rejected() : void
    {
        $this->expectException(InvalidArgumentException::class);

        TriviaAnswerTiming::calculate(0.0, 5000.0, 10000);
    }

    public function test_response_after_deadline_is_late_and_recorded_time_is_capped() : void
    {
        $timing = TriviaAnswerTiming::calculate(1000.0, 12001.0, 10000);

        $this->assertSame(11001, $timing['elapsed_ms']);
        $this->assertSame(10000, $timing['response_time_ms']);
        $this->assertTrue($timing['is_late']);
    }

    public function test_response_at_exact_deadline_is_allowed() : void
    {
        $timing = TriviaAnswerTiming::calculate(1000.0, 11000.0, 10000);

        $this->assertSame(10000, $timing['response_time_ms']);
        $this->assertFalse($timing['is_late']);
    }

    public function test_response_inside_deadline_is_not_late() : void
    {
        $timing = TriviaAnswerTiming::calculate(1000.0, 8500.0, 10000);

        $this->assertSame(7500, $timing['elapsed_ms']);
        $this->assertSame(7500, $timing['response_time_ms']);
        $this->assertFalse($timing['is_late']);
    }
}
