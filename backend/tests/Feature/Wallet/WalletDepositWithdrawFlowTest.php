<?php

namespace Tests\Feature\Wallet;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletLimit;
use App\Domains\Wallet\Models\WalletMovement;
use App\Http\Middleware\RequireTosAcceptance;
use App\Models\Currency;
use App\Models\Directories\Crypto\Binance\BinanceAccount;
use App\Models\MovementType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Billetera de punta a punta (auditoría v1), todo por HTTP: el jugador
 * deposita y tesorería lo aprueba; retira y tesorería paga con referencia;
 * un segundo retiro se rechaza y el saldo vuelve al jugador.
 */
class WalletDepositWithdrawFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_then_withdraw_approved_and_rejected() : void
    {
        (new MovementTypeSeeder)->run();
        MovementType::forgetCatalogCache();
        foreach (['wallet.view', 'wallet.deposit', 'wallet.withdraw', 'wallet.movements.view', 'wallet.movements.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        // Aceptar TOS no es lo que se prueba aquí.
        $this->withoutMiddleware(RequireTosAcceptance::class);
        Storage::fake('local');

        $currency = Currency::firstOrCreate(['iso_code' => 'VES'], ['name' => 'Bolívar', 'type' => 'FIAT']);
        WalletLimit::create([
            'currency_id' => $currency->id, 'kyc_level' => 2,
            'min_deposit' => 1, 'max_deposit' => 1000, 'min_per_transaction' => 1, 'max_per_transaction' => 500,
            'max_daily' => 1000, 'max_weekly' => 3000, 'max_monthly' => 10000, 'is_active' => true,
        ]);
        WalletLimit::forgetCatalogCache();

        $player = User::factory()->create(['email_verified_at' => now()]);
        // Retirar exige KYC (config wallet.withdrawal_min_kyc_level); ver test aparte.
        $player->forceFill(['kyc_level' => 2])->save();
        $player->givePermissionTo(['wallet.view', 'wallet.deposit', 'wallet.withdraw']);
        $player->forceFill(['withdrawal_pin_hash' => Hash::make('123456'), 'withdrawal_pin_set_at' => now()])->save();
        $wallet = Wallet::create([
            'user_id' => $player->id, 'currency_id' => $currency->id,
            'balance' => 0, 'bonus_balance' => 0, 'status' => 'ACTIVE',
        ]);
        $directory = BinanceAccount::create([
            'account_type' => 'E', 'data_value' => 'jugador@example.local',
            'user_id' => (string) $player->id, 'status' => 'ACTIVE',
        ]);
        $treasurer = $this->treasurer();

        // 1. Depósito: queda pendiente y no acredita hasta que se aprueba.
        $this->actingAs($player)->post('/wallet/deposit', [
            'currency_id' => (string) $currency->id,
            'amount' => 50,
            'receipt' => UploadedFile::fake()->image('comprobante.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();
        $deposit = WalletMovement::where('wallet_id', $wallet->id)->where('type', WalletMovement::TYPE_DEPOSIT)->sole();
        $this->assertSame(WalletMovement::STATUS_PENDING, $deposit->status);
        $this->assertEqualsWithDelta(0.0, (float) $wallet->fresh()->balance, 0.001);

        $this->actingAs($treasurer)->postJson(route('wallet-movements.approve', $deposit))->assertOk();
        $this->assertEqualsWithDelta(50.0, (float) $wallet->fresh()->balance, 0.001);

        // 2. Retiro aprobado: se descuenta al solicitar; pagar exige referencia.
        $withdraw = fn (float $amount) => $this->actingAs($player)->postJson('/wallet/withdraw', [
            'currency_id' => (string) $currency->id,
            'amount' => $amount,
            'directory_id' => (string) $directory->id,
            'directory_type' => 'binance',
            'withdrawal_pin' => '123456',
        ]);

        $withdraw(20)->assertOk()->assertJsonPath('balance', 30);
        $paid = WalletMovement::where('wallet_id', $wallet->id)->where('type', WalletMovement::TYPE_WITHDRAWAL)->sole();
        $this->actingAs($treasurer)->postJson(route('wallet-movements.approve', $paid), ['payout_reference' => 'BIN-777'])
            ->assertOk()
            ->assertJsonPath('data.status', WalletMovement::STATUS_COMPLETED);
        $this->assertSame('BIN-777', $paid->fresh()->reference_id);
        $this->assertEqualsWithDelta(30.0, (float) $wallet->fresh()->balance, 0.001);

        // 3. Retiro rechazado: el saldo retenido vuelve con un movimiento de reverso.
        $withdraw(10)->assertOk();
        $this->assertEqualsWithDelta(20.0, (float) $wallet->fresh()->balance, 0.001);
        $rejected = WalletMovement::where('wallet_id', $wallet->id)->where('type', WalletMovement::TYPE_WITHDRAWAL)
            ->where('status', WalletMovement::STATUS_PENDING)->sole();
        $this->actingAs($treasurer)->postJson(route('wallet-movements.reject', $rejected), [
            'player_message' => 'Los datos de la cuenta destino no coinciden con tu identidad.',
        ])->assertOk();

        $this->assertEqualsWithDelta(30.0, (float) $wallet->fresh()->balance, 0.001);
        $this->assertTrue(WalletMovement::where('wallet_id', $wallet->id)
            ->where('type', WalletMovement::TYPE_WITHDRAWAL_REVERSAL)->exists());
    }

    private function treasurer() : User
    {
        if (! Role::where('name', 'admin')->where('guard_name', 'web')->exists()) {
            $role = new Role(['name' => 'admin', 'guard_name' => 'web', 'prefix' => 'ADMIN', 'type' => 'web']);
            $role->id = Role::generateSnowflakeId();
            $role->save();
        }
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo(['wallet.movements.view', 'wallet.movements.manage']);

        return $user;
    }
}
