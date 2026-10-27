<?php

namespace Tests\Feature\Wallet;

use App\Domains\Wallet\Models\WalletLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletLimitControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp() : void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('wallet-limits.view');
        $this->admin->givePermissionTo('wallet-limits.manage');
    }

    public function test_delete_allows_removal_of_currency_specific_limit() : void
    {
        $currency = \App\Models\Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
        $limit = WalletLimit::create($this->validPayload(['currency_id' => $currency->id]));

        $response = $this->actingAs($this->admin)
            ->putJson("/wallet-limits/{$limit->id}/delete");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('wallet_limits', ['id' => $limit->id]);
    }

    public function test_delete_blocks_removal_of_global_fallback_limit() : void
    {
        $limit = WalletLimit::create($this->validPayload(['currency_id' => null]));

        $response = $this->actingAs($this->admin)
            ->putJson("/wallet-limits/{$limit->id}/delete");

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseHas('wallet_limits', ['id' => $limit->id]);
    }

    public function test_guest_cannot_access_wallet_limits() : void
    {
        $this->getJson('/wallet-limits')->assertUnauthorized();
    }

    public function test_store_creates_wallet_limit() : void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/wallet-limits', $this->validPayload());

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseCount('wallet_limits', 1);
    }

    public function test_store_rejects_duplicate_currency_and_kyc_level_combination() : void
    {
        $this->actingAs($this->admin)->postJson('/wallet-limits', $this->validPayload());

        $response = $this->actingAs($this->admin)
            ->postJson('/wallet-limits', $this->validPayload());

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseCount('wallet_limits', 1);
    }

    public function test_toggle_status_flips_is_active() : void
    {
        $limit = WalletLimit::create($this->validPayload(['is_active' => true]));

        $response = $this->actingAs($this->admin)
            ->putJson("/wallet-limits/{$limit->id}/status");

        $response->assertOk();
        $this->assertFalse((bool) $limit->fresh()->is_active);
    }

    private function validPayload(array $overrides = []) : array
    {
        return array_merge([
            'currency_id' => null,
            'kyc_level' => 0,
            'min_deposit' => 1,
            'max_deposit' => 1000,
            'min_per_transaction' => 1,
            'max_per_transaction' => 500,
            'max_daily' => 500,
            'max_weekly' => 1000,
            'max_monthly' => 2000,
            'is_active' => true,
        ], $overrides);
    }
}
