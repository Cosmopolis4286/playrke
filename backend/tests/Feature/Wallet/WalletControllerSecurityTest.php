<?php

namespace Tests\Feature\Wallet;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletLimitService;
use App\Domains\Wallet\Services\WalletService;
use App\Http\Controllers\WalletController;
use App\Models\Currency;
use App\Models\User;
use App\Services\Roles\RoleUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class WalletControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_receipt_is_served_from_private_storage_to_its_owner(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $currency = Currency::firstOrCreate(
            ['iso_code' => 'USD'],
            ['name' => 'Dollar', 'type' => 'FIAT'],
        );
        $wallet = Wallet::create([
            'user_id' => $owner->id,
            'currency_id' => $currency->id,
            'balance' => 0,
            'bonus_balance' => 0,
            'status' => 'ACTIVE',
        ]);

        $filename = 'receipt-private-test.pdf';
        $receiptPath = 'wallet/receipts/' . $filename;
        Storage::disk('local')->put($receiptPath, 'private receipt');

        WalletMovement::create([
            'wallet_id' => $wallet->id,
            'user_id' => $owner->id,
            'type' => WalletMovement::TYPE_DEPOSIT,
            'status' => WalletMovement::STATUS_PENDING,
            'amount' => 10,
            'balance_before' => 0,
            'balance_after' => 0,
            'receipt_path' => $receiptPath,
        ]);

        $this->actingAs($owner)
            ->get(route('wallet.receipt', ['filename' => $filename]))
            ->assertOk()
            ->assertSee('private receipt');

        Storage::disk('public')->assertMissing($receiptPath);
    }

    public function test_wallet_receipt_cannot_be_opened_by_another_user(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $currency = Currency::firstOrCreate(
            ['iso_code' => 'USD'],
            ['name' => 'Dollar', 'type' => 'FIAT'],
        );
        $wallet = Wallet::create([
            'user_id' => $owner->id,
            'currency_id' => $currency->id,
            'balance' => 0,
            'bonus_balance' => 0,
            'status' => 'ACTIVE',
        ]);

        $filename = 'another-users-receipt.pdf';
        $receiptPath = 'wallet/receipts/' . $filename;
        Storage::disk('local')->put($receiptPath, 'private receipt');

        WalletMovement::create([
            'wallet_id' => $wallet->id,
            'user_id' => $owner->id,
            'type' => WalletMovement::TYPE_DEPOSIT,
            'status' => WalletMovement::STATUS_PENDING,
            'amount' => 10,
            'balance_before' => 0,
            'balance_after' => 0,
            'receipt_path' => $receiptPath,
        ]);

        $this->actingAs($otherUser)
            ->get(route('wallet.receipt', ['filename' => $filename]))
            ->assertForbidden();
    }

    public function test_pending_operation_returns_server_error_when_lookup_fails(): void
    {
        $wallet = new Wallet();
        $wallet->id = 'wallet-test-id';

        $walletService = Mockery::mock(WalletService::class);
        $walletService->shouldReceive('findOwnedWalletByCurrency')
            ->once()
            ->with('currency-test-id')
            ->andReturn($wallet);
        $walletService->shouldReceive('getPendingOperation')
            ->once()
            ->with('wallet-test-id', 'DEPOSIT')
            ->andThrow(new \RuntimeException('database unavailable'));

        $controller = new WalletController(
            $walletService,
            Mockery::mock(WalletLimitService::class),
            Mockery::mock(RoleUserService::class),
        );

        $response = $controller->pendingOperation(Request::create('/wallet/pending-operation', 'GET', [
            'currency_id' => 'currency-test-id',
            'type' => 'DEPOSIT',
        ]));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
        $this->assertArrayNotHasKey('pending', $response->getData(true));
    }
}
