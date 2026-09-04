<?php

namespace Tests\Unit\Domains\Marketplace\Crypto;

use App\Domains\Marketplace\Crypto\DTOs\RawCryptoTransaction;
use App\Domains\Marketplace\Crypto\Exceptions\InvalidCryptoTransactionException;
use App\Domains\Marketplace\Crypto\Models\CryptoDeposit;
use App\Domains\Marketplace\Crypto\Models\CryptoTransaction;
use App\Domains\Marketplace\Crypto\Models\DepositAddress;
use App\Domains\Marketplace\Crypto\Services\DepositWatcher;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositWatcherTest extends TestCase
{
    use RefreshDatabase;

    private DepositWatcher $watcher;
    private Currency $usdt;
    private User $user;
    private Wallet $wallet;
    private DepositAddress $depositAddress;

    protected function setUp(): void
    {
        parent::setUp();

        (new MovementTypeSeeder())->run();
        MovementType::forgetCatalogCache();

        config([
            'crypto.tokens.usdt' => ['network' => 'TRON', 'contract_address' => 'fake-contract'],
            'crypto.min_confirmations' => 19,
        ]);

        $this->watcher = app(DepositWatcher::class);
        $this->usdt = Currency::firstOrCreate(['iso_code' => 'USDT'], ['name' => 'Tether', 'type' => 'CRYPTO']);
        $this->user = User::factory()->create();
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id, 'currency_id' => $this->usdt->id, 'balance' => 0, 'status' => 'ACTIVE',
        ]);
        $this->depositAddress = DepositAddress::create([
            'user_id' => $this->user->id, 'currency_id' => $this->usdt->id, 'network' => 'TRON',
            'derivation_index' => 1, 'address' => 'TFAKEADDRESS', 'status' => 'ACTIVE',
        ]);
    }

    private function tx(string $hash, array $overrides = []): RawCryptoTransaction
    {
        return new RawCryptoTransaction(
            txHash: $hash,
            network: $overrides['network'] ?? 'TRON',
            token: $overrides['token'] ?? 'USDT',
            fromAddress: $overrides['from'] ?? 'TFROM',
            toAddress: $overrides['to'] ?? $this->depositAddress->address,
            amount: $overrides['amount'] ?? 10.0,
            confirmations: $overrides['confirmations'] ?? 20,
        );
    }

    public function test_rejects_network_not_in_whitelist(): void
    {
        $this->expectException(InvalidCryptoTransactionException::class);
        $this->watcher->process($this->tx('tx1', ['network' => 'ETHEREUM']));
    }

    public function test_rejects_token_not_matching_network(): void
    {
        config(['crypto.tokens.usdt' => ['network' => 'ETHEREUM', 'contract_address' => 'x']]);

        $this->expectException(InvalidCryptoTransactionException::class);
        $this->watcher->process($this->tx('tx2'));
    }

    public function test_rejects_insufficient_confirmations(): void
    {
        $this->expectException(InvalidCryptoTransactionException::class);
        $this->watcher->process($this->tx('tx3', ['confirmations' => 5]));
    }

    public function test_rejects_unassigned_destination_address(): void
    {
        $this->expectException(InvalidCryptoTransactionException::class);
        $this->watcher->process($this->tx('tx4', ['to' => 'TUNASSIGNED']));
    }

    public function test_happy_path_credits_wallet_exactly(): void
    {
        $deposit = $this->watcher->process($this->tx('tx-happy', ['amount' => 25.5]));

        $this->assertTrue($deposit->isCredited());
        $this->assertSame(25.5, (float) $this->wallet->fresh()->balance);

        $transaction = CryptoTransaction::find($deposit->crypto_transaction_id);
        $this->assertSame(CryptoTransaction::STATUS_CREDITED, $transaction->status);
    }

    public function test_replaying_same_tx_hash_does_not_double_credit(): void
    {
        $first = $this->watcher->process($this->tx('tx-replay', ['amount' => 25.5]));
        $second = $this->watcher->process($this->tx('tx-replay', ['amount' => 25.5]));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(25.5, (float) $this->wallet->fresh()->balance);
        $this->assertSame(
            1,
            WalletMovement::where('idempotency_key', 'crypto_deposit:tx-replay')->count(),
        );
        $this->assertSame(1, CryptoTransaction::where('tx_hash', 'tx-replay')->count());
    }

    public function test_rejects_when_user_has_no_active_wallet_for_currency(): void
    {
        $this->wallet->update(['status' => 'BLOCKED']);

        $this->expectException(InvalidCryptoTransactionException::class);
        $this->watcher->process($this->tx('tx5'));
    }
}
