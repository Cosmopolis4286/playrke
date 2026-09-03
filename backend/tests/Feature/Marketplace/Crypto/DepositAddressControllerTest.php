<?php

namespace Tests\Feature\Marketplace\Crypto;

use App\Domains\Marketplace\Crypto\Models\DepositAddress;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositAddressControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Currency::firstOrCreate(['iso_code' => 'USDT'], ['name' => 'Tether', 'type' => 'CRYPTO']);
    }

    public function test_returns_403_when_deposits_disabled(): void
    {
        config(['crypto.deposits_enabled' => false]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/wallet/deposit-address?currency=USDT');

        $response->assertStatus(403);
    }

    public function test_returns_stub_address_when_enabled(): void
    {
        config(['crypto.deposits_enabled' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/wallet/deposit-address?currency=USDT');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.network', 'TRON')
            ->assertJsonPath('data.currency', 'USDT');

        $this->assertSame(1, DepositAddress::where('user_id', $user->id)->count());
    }

    public function test_returns_404_for_unknown_currency(): void
    {
        config(['crypto.deposits_enabled' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/wallet/deposit-address?currency=DOES_NOT_EXIST');

        $response->assertStatus(404);
    }
}
