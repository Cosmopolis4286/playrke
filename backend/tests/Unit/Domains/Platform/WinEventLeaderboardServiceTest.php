<?php

namespace Tests\Unit\Domains\Platform;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Platform\WinEvent\Services\WinEventLeaderboardService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WinEventLeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private WinEventLeaderboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WinEventLeaderboardService::class);
    }

    private function createMoneyWin(?string $userId, float $amountUsd, ?\DateTimeInterface $createdAt = null): WinEvent
    {
        $winEvent = WinEvent::create([
            'user_id'     => $userId,
            'game_slug'   => 'bingo',
            'game_name'   => 'Bingo',
            'source_type' => WinEvent::SOURCE_GAME_ENGINE,
            'prize_kind'  => WinEvent::PRIZE_MONEY,
            'prize_label' => '$' . number_format($amountUsd, 2),
            'amount_usd'  => $amountUsd,
        ]);

        if ($createdAt) {
            $winEvent->forceFill(['created_at' => $createdAt])->save();
        }

        return $winEvent;
    }

    public function test_groups_and_sums_amount_per_user_ordered_desc(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->createMoneyWin($userA->id, 10.00);
        $this->createMoneyWin($userA->id, 15.00);
        $this->createMoneyWin($userB->id, 50.00);

        $result = $this->service->weeklyTopWinners()->values();

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['rank']);
        $this->assertEqualsWithDelta(50.00, $result[0]['total_amount'], 0.001);
        $this->assertSame(1, $result[0]['wins_count']);

        $this->assertSame(2, $result[1]['rank']);
        $this->assertEqualsWithDelta(25.00, $result[1]['total_amount'], 0.001);
        $this->assertSame(2, $result[1]['wins_count']);
    }

    public function test_excludes_events_without_user_id(): void
    {
        $this->createMoneyWin(null, 999.00);

        $result = $this->service->weeklyTopWinners();

        $this->assertCount(0, $result);
    }

    public function test_excludes_non_money_prizes(): void
    {
        $user = User::factory()->create();

        WinEvent::create([
            'user_id'     => $user->id,
            'game_slug'   => 'bingo',
            'game_name'   => 'Bingo',
            'source_type' => WinEvent::SOURCE_ADMIN_MANUAL,
            'prize_kind'  => WinEvent::PRIZE_ITEM,
            'prize_label' => 'PlayStation 5',
        ]);

        $result = $this->service->weeklyTopWinners();

        $this->assertCount(0, $result);
    }

    public function test_excludes_events_older_than_seven_days(): void
    {
        $user = User::factory()->create();

        $this->createMoneyWin($user->id, 100.00, now()->subDays(8));

        $result = $this->service->weeklyTopWinners();

        $this->assertCount(0, $result);
    }

    public function test_winner_name_is_masked(): void
    {
        $user = User::factory()->create(['first_name' => 'Bernardo', 'last_name' => 'Perez']);

        $this->createMoneyWin($user->id, 100.00);

        $result = $this->service->weeklyTopWinners()->values();

        $this->assertStringNotContainsString('Bernardo Perez', $result[0]['winner_name']);
        $this->assertStringContainsString('*', $result[0]['winner_name']);
    }
}
