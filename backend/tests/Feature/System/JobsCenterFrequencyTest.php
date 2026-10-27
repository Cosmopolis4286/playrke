<?php

namespace Tests\Feature\System;

use App\Models\Commands\ScheduledCommand;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura del feature de frecuencia dinámica del panel Jobs/Scheduler —
 * ver JobsCenterController::updateScheduledCommandFrequency() /
 * previewScheduledCommandFrequency() / resetScheduledCommandFrequency() y
 * App\Rules\CronExpressionRule.
 */
class JobsCenterFrequencyTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    protected function setUp() : void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_cron_more_aggressive_than_floor_is_rejected() : void
    {
        $superadmin = $this->makeSuperadmin();
        $scheduled = $this->makeScheduledCommand(minIntervalSeconds: 300);

        $response = $this->actingAs($superadmin)
            ->putJson("/system/jobs/scheduler/{$scheduled->id}/frequency", ['cron_expression' => '* * * * *']);

        $response->assertStatus(422)->assertJsonValidationErrors('cron_expression');
        $this->assertNull($scheduled->fresh()->cron_expression);
    }

    public function test_cron_respecting_floor_is_accepted() : void
    {
        $superadmin = $this->makeSuperadmin();
        $scheduled = $this->makeScheduledCommand(minIntervalSeconds: 300);

        $response = $this->actingAs($superadmin)
            ->putJson("/system/jobs/scheduler/{$scheduled->id}/frequency", ['cron_expression' => '*/5 * * * *']);

        $response->assertOk();
        $this->assertSame('*/5 * * * *', $scheduled->fresh()->cron_expression);
    }

    public function test_invalid_cron_syntax_is_rejected() : void
    {
        $superadmin = $this->makeSuperadmin();
        $scheduled = $this->makeScheduledCommand();

        $response = $this->actingAs($superadmin)
            ->putJson("/system/jobs/scheduler/{$scheduled->id}/frequency", ['cron_expression' => 'not a cron']);

        $response->assertStatus(422)->assertJsonValidationErrors('cron_expression');
        $this->assertNull($scheduled->fresh()->cron_expression);
    }

    public function test_non_superadmin_cannot_update_frequency() : void
    {
        $player = $this->makePlayer();
        $scheduled = $this->makeScheduledCommand();

        $this->actingAs($player)
            ->putJson("/system/jobs/scheduler/{$scheduled->id}/frequency", ['cron_expression' => '* * * * *'])
            ->assertStatus(403);

        $this->assertNull($scheduled->fresh()->cron_expression);
    }

    public function test_preview_returns_next_runs_without_persisting() : void
    {
        $superadmin = $this->makeSuperadmin();
        $scheduled = $this->makeScheduledCommand();

        $response = $this->actingAs($superadmin)
            ->postJson("/system/jobs/scheduler/{$scheduled->id}/frequency/preview", ['cron_expression' => '0 * * * *']);

        $response->assertOk()
            ->assertJsonStructure(['success', 'data' => ['frequency', 'next_runs']])
            ->assertJsonCount(5, 'data.next_runs');

        $this->assertNull($scheduled->fresh()->cron_expression);
    }

    public function test_reset_frequency_clears_override() : void
    {
        $superadmin = $this->makeSuperadmin();
        $scheduled = $this->makeScheduledCommand();
        $scheduled->update(['cron_expression' => '*/10 * * * *']);

        $response = $this->actingAs($superadmin)
            ->deleteJson("/system/jobs/scheduler/{$scheduled->id}/frequency");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertNull($scheduled->fresh()->cron_expression);
    }

    public function test_superadmin_can_update_frequency_with_valid_cron() : void
    {
        $superadmin = $this->makeSuperadmin();
        $scheduled = $this->makeScheduledCommand();

        $response = $this->actingAs($superadmin)
            ->putJson("/system/jobs/scheduler/{$scheduled->id}/frequency", ['cron_expression' => '*/10 * * * *']);

        $response->assertOk()->assertJson(['success' => true]);

        $fresh = $scheduled->fresh();
        $this->assertSame('*/10 * * * *', $fresh->cron_expression);
        $this->assertSame($superadmin->id, $fresh->frequency_updated_by);
        $this->assertNotNull($fresh->frequency_updated_at);
    }

    private function makePlayer() : User
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        return $user;
    }

    private function makeScheduledCommand(?int $minIntervalSeconds = null) : ScheduledCommand
    {
        return ScheduledCommand::create([
            'command' => 'test:dummy-command',
            'label' => 'Comando de prueba',
            'description' => 'Solo para tests.',
            'min_interval_seconds' => $minIntervalSeconds,
        ]);
    }

    private function makeSuperadmin() : User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    private function nextId() : string
    {
        return (string) (500000000000000000 + (++self::$idCounter));
    }

    private function seedRoles() : void
    {
        foreach (['superadmin', 'player'] as $name) {
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                $role = new Role([
                    'name' => $name,
                    'guard_name' => 'web',
                    'prefix' => $name,
                    'type' => 'web',
                    'status' => true,
                ]);
                $role->id = $this->nextId();
                $role->save();
            }
        }
    }
}
