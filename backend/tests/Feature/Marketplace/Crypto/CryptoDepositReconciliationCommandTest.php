<?php

namespace Tests\Feature\Marketplace\Crypto;

use App\Domains\Marketplace\Crypto\Models\CryptoTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CryptoDepositReconciliationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_stale_confirmed_deposits() : void
    {
        Log::spy();

        $this->makeTransaction();

        $this->artisan('marketplace:reconcile-crypto-deposits')->assertExitCode(0);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'Depósito confirmado sin acreditar'));
    }

    public function test_silent_when_confirmed_recently() : void
    {
        Log::spy();

        $this->makeTransaction(['confirmed_at' => now()->subMinutes(2)]);

        $this->artisan('marketplace:reconcile-crypto-deposits')->assertExitCode(0);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_silent_when_deposit_already_credited() : void
    {
        Log::spy();

        $this->makeTransaction(['status' => CryptoTransaction::STATUS_CREDITED]);

        $this->artisan('marketplace:reconcile-crypto-deposits')->assertExitCode(0);

        Log::shouldNotHaveReceived('warning');
    }

    private function makeTransaction(array $overrides = []) : CryptoTransaction
    {
        $user = User::factory()->create();

        return CryptoTransaction::create(array_merge([
            'tx_hash' => 'tx-' . uniqid(),
            'network' => 'TRON',
            'token' => 'USDT',
            'direction' => CryptoTransaction::DIRECTION_DEPOSIT,
            'from_address' => 'from',
            'to_address' => 'to',
            'amount' => 10.0,
            'status' => CryptoTransaction::STATUS_CONFIRMED,
            'confirmations' => 20,
            'user_id' => $user->id,
            'confirmed_at' => now()->subMinutes(30),
        ], $overrides));
    }
}
