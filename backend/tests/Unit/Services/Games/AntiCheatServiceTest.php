<?php

namespace Tests\Unit\Services\Games;

use App\Enums\Games\Common\AntiCheatSignalType;
use App\Enums\Games\Common\GameSlug;
use App\Models\AntiCheat\AntiCheatFlag;
use App\Models\AntiCheat\UserSuspension;
use App\Models\User;
use App\Services\Games\AntiCheatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AntiCheatServiceTest extends TestCase
{
    use RefreshDatabase;

    private AntiCheatService $service;

    protected function setUp() : void
    {
        parent::setUp();

        $this->service = app(AntiCheatService::class);

        config([
            'anticheat.suspension_threshold' => 5,
            'anticheat.window_minutes' => 1440,
        ]);
    }

    public function test_does_not_create_a_second_suspension_if_already_active() : void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->service->flag((string) $user->id, GameSlug::TRIVIA, AntiCheatSignalType::IMPOSSIBLE_CLAIM_TIMING);
        }

        $count = UserSuspension::where('user_id', $user->id)->whereNull('resolved_at')->count();
        $this->assertEquals(1, $count);
    }

    public function test_five_severity_one_flags_trigger_suspension_at_threshold() : void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->service->flag(
                (string) $user->id,
                GameSlug::TRIVIA,
                AntiCheatSignalType::MISSING_CLIENT_TIMESTAMP, // severity=1
            );
        }

        $this->assertEquals('SUSPENDED', $user->fresh()->status);

        $suspension = UserSuspension::where('user_id', $user->id)->whereNull('resolved_at')->first();
        $this->assertNotNull($suspension);
        $this->assertEquals(UserSuspension::ORIGIN_ANTI_CHEAT, $suspension->origin);
    }

    public function test_flag_persists_a_row_with_correct_fields() : void
    {
        $user = User::factory()->create();

        $flag = $this->service->flag(
            (string) $user->id,
            GameSlug::TRIVIA,
            AntiCheatSignalType::INSTANT_RESPONSE,
            'game-123',
            ['response_time_ms' => 42],
        );

        $this->assertDatabaseHas('anti_cheat_flags', [
            'id' => $flag->id,
            'user_id' => (string) $user->id,
            'game_slug' => 'trivia',
            'game_id' => 'game-123',
            'signal_type' => 'INSTANT_RESPONSE',
            'severity' => 3, // defaultSeverity() de INSTANT_RESPONSE
        ]);

        $this->assertEquals(['response_time_ms' => 42], $flag->fresh()->metadata);
    }

    public function test_recent_severity_filtered_by_game_does_not_mix_other_games() : void
    {
        $user = User::factory()->create();

        $this->service->flag((string) $user->id, GameSlug::TRIVIA, AntiCheatSignalType::INSTANT_RESPONSE); // severity=3
        $this->service->flag((string) $user->id, GameSlug::BINGO, AntiCheatSignalType::IMPOSSIBLE_CLAIM_TIMING); // severity=3

        $this->assertEquals(3, $this->service->recentSeverity((string) $user->id, GameSlug::TRIVIA));
        $this->assertEquals(3, $this->service->recentSeverity((string) $user->id, GameSlug::BINGO));
        $this->assertEquals(6, $this->service->recentSeverity((string) $user->id));
    }

    public function test_recent_severity_ignores_flags_outside_the_window() : void
    {
        $user = User::factory()->create();

        $oldFlag = AntiCheatFlag::create([
            'user_id' => $user->id,
            'game_slug' => 'trivia',
            'signal_type' => AntiCheatSignalType::INSTANT_RESPONSE,
            'severity' => 3,
        ]);
        $oldFlag->created_at = now()->subDays(2);
        $oldFlag->save();

        $this->service->flag((string) $user->id, GameSlug::TRIVIA, AntiCheatSignalType::MISSING_CLIENT_TIMESTAMP); // severity=1, dentro de ventana

        $this->assertEquals(1, $this->service->recentSeverity((string) $user->id));
    }

    public function test_two_severity_three_flags_trigger_suspension_via_accumulated_sum() : void
    {
        $user = User::factory()->create();

        $this->service->flag((string) $user->id, GameSlug::TRIVIA, AntiCheatSignalType::INSTANT_RESPONSE); // severity=3
        $this->assertEquals('ACTIVE', $user->fresh()->status);

        $second = $this->service->flag((string) $user->id, GameSlug::BINGO, AntiCheatSignalType::IMPOSSIBLE_CLAIM_TIMING); // severity=3, total=6

        $this->assertEquals('SUSPENDED', $user->fresh()->status);
        $this->assertTrue($second->fresh()->triggered_suspension);
    }
}
