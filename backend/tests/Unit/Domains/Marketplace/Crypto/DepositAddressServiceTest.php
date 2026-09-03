<?php

namespace Tests\Unit\Domains\Marketplace\Crypto;

use App\Domains\Marketplace\Crypto\Contracts\CryptoGateway;
use App\Domains\Marketplace\Crypto\Exceptions\CryptoCustodyNotConfiguredException;
use App\Domains\Marketplace\Crypto\Models\DepositAddress;
use App\Domains\Marketplace\Crypto\Providers\TronUsdtProvider;
use App\Domains\Marketplace\Crypto\Services\DepositAddressService;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositAddressServiceTest extends TestCase
{
    use RefreshDatabase;

    private DepositAddressService $service;
    private Currency $usdt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DepositAddressService::class);
        $this->usdt = Currency::firstOrCreate(['iso_code' => 'USDT'], ['name' => 'Tether', 'type' => 'CRYPTO']);
    }

    public function test_gateway_resolves_to_stub_provider(): void
    {
        $this->assertInstanceOf(TronUsdtProvider::class, app(CryptoGateway::class));
    }

    public function test_get_or_create_creates_new_address(): void
    {
        $user = User::factory()->create();

        $address = $this->service->getOrCreate((string) $user->id, (string) $this->usdt->id);

        $this->assertSame(DepositAddress::NETWORK_TRON, $address->network);
        $this->assertSame(DepositAddress::STATUS_ACTIVE, $address->status);
        $this->assertNotEmpty($address->address);
    }

    public function test_get_or_create_is_idempotent(): void
    {
        $user = User::factory()->create();

        $first = $this->service->getOrCreate((string) $user->id, (string) $this->usdt->id);
        $second = $this->service->getOrCreate((string) $user->id, (string) $this->usdt->id);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DepositAddress::where('user_id', $user->id)->count());
    }

    public function test_different_users_get_different_addresses(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $addressA = $this->service->getOrCreate((string) $userA->id, (string) $this->usdt->id);
        $addressB = $this->service->getOrCreate((string) $userB->id, (string) $this->usdt->id);

        $this->assertNotSame($addressA->address, $addressB->address);
        $this->assertNotSame($addressA->derivation_index, $addressB->derivation_index);
    }

    public function test_get_or_create_by_currency_code(): void
    {
        $user = User::factory()->create();

        $address = $this->service->getOrCreateByCurrencyCode((string) $user->id, 'usdt');

        $this->assertSame((string) $this->usdt->id, $address->currency_id);
    }

    public function test_stub_provider_throws_on_broadcast_withdrawal(): void
    {
        $this->expectException(CryptoCustodyNotConfiguredException::class);
        app(CryptoGateway::class)->broadcastWithdrawal('any-address', 10.0);
    }

    public function test_stub_provider_throws_on_get_confirmations(): void
    {
        $this->expectException(CryptoCustodyNotConfiguredException::class);
        app(CryptoGateway::class)->getConfirmations('any-tx-hash');
    }
}
