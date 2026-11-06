<?php

namespace Tests\Feature\Wallet;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            ->assertHeader('content-type', 'application/pdf');

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

    public function test_receipt_migration_moves_public_files_to_private_storage(): void
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

        $filename = 'legacy-public-receipt.pdf';
        $receiptPath = 'wallet/receipts/' . $filename;
        Storage::disk('public')->put($receiptPath, 'legacy receipt');

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

        $this->artisan('wallet:migrate-receipts-private')
            ->assertExitCode(0);
        Storage::disk('public')->assertExists($receiptPath);
        Storage::disk('local')->assertMissing($receiptPath);

        $this->artisan('wallet:migrate-receipts-private --apply')
            ->assertExitCode(0);
        Storage::disk('local')->assertExists($receiptPath);
        Storage::disk('public')->assertMissing($receiptPath);
    }


}
