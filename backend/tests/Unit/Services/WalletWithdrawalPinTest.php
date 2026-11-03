<?php

namespace Tests\Unit\Services;

use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletLimit;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletFraudService;
use App\Domains\Wallet\Services\WalletService;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WalletWithdrawalPinTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    protected function setUp() : void
    {
        parent::setUp();

        $this->service = app(WalletService::class);

        (new MovementTypeSeeder)->run();
        MovementType::forgetCatalogCache();
    }

    public function test_correct_pin_clears_previous_failed_attempts() : void
    {
        $wallet = $this->makeWalletWithUser(1000.0, pin: '123456');
        $dir = $this->makeDirectory($wallet);
        $userId = (string) $wallet->user_id;

        // 3 fallos (por debajo del umbral de bloqueo de 5)
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->service->requestWithdraw($wallet, 50.0, $dir['directoryId'], $dir['directoryType'], withdrawalPin: '000000');
            } catch (WalletDomainException $e) {
            }
        }

        // Acierta: limpia el contador
        $this->service->requestWithdraw($wallet, 50.0, $dir['directoryId'], $dir['directoryType'], withdrawalPin: '123456');

        // Otros 3 fallos tras el acierto no deberían sumar a los 3 previos
        // (si no se limpió, este sería el 6to fallo acumulado y bloquearía).
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->service->requestWithdraw($wallet->fresh(), 10.0, $dir['directoryId'], $dir['directoryType'], withdrawalPin: '000000');
            } catch (WalletDomainException $e) {
                $this->assertStringNotContainsString('Demasiados intentos', $e->getMessage());
            }
        }

        app(WalletFraudService::class)->clearWithdrawalPinAttempts($userId);
    }

    // ── setWithdrawalPin() ───────────────────────────────────────────────────

    public function test_set_withdrawal_pin_hashes_and_persists() : void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->hasWithdrawalPin());

        $this->service->setWithdrawalPin($user, '123456');

        $user->refresh();
        $this->assertTrue($user->hasWithdrawalPin());
        $this->assertTrue(Hash::check('123456', $user->withdrawal_pin_hash));
        $this->assertNotNull($user->withdrawal_pin_set_at);
    }

    // ── requestWithdraw() — PIN configurado, feliz y fallos ──────────────────

    public function test_withdraw_with_correct_pin_succeeds() : void
    {
        $wallet = $this->makeWalletWithUser(1000.0, pin: '123456');
        $dir = $this->makeDirectory($wallet);

        $result = $this->service->requestWithdraw($wallet, 50.0, $dir['directoryId'], $dir['directoryType'], withdrawalPin: '123456');

        $this->assertSame(950.0, (float) $result->balance);
    }

    public function test_withdraw_with_missing_pin_throws_withdrawal_pin_required() : void
    {
        $wallet = $this->makeWalletWithUser(1000.0, pin: '123456');
        $dir = $this->makeDirectory($wallet);

        $this->expectException(WalletDomainException::class);
        $this->expectExceptionMessage('Ingresa tu PIN de retiro');

        $this->service->requestWithdraw($wallet, 50.0, $dir['directoryId'], $dir['directoryType'], withdrawalPin: null);
    }

    public function test_withdraw_with_wrong_pin_throws_and_does_not_touch_balance() : void
    {
        $wallet = $this->makeWalletWithUser(1000.0, pin: '123456');
        $dir = $this->makeDirectory($wallet);

        $this->expectException(WalletDomainException::class);

        try {
            $this->service->requestWithdraw($wallet, 50.0, $dir['directoryId'], $dir['directoryType'], withdrawalPin: '000000');
        } finally {
            $this->assertSame(1000.0, (float) $wallet->fresh()->balance);
            $this->assertSame(0, WalletMovement::where('wallet_id', $wallet->id)->count());
        }
    }

    // ── requestWithdraw() — PIN no configurado ───────────────────────────────

    public function test_withdraw_without_pin_configured_throws_withdrawal_pin_not_set() : void
    {
        $wallet = $this->makeWalletWithUser(1000.0, pin: null);
        $dir = $this->makeDirectory($wallet);

        $this->expectException(WalletDomainException::class);
        $this->expectExceptionMessage('Debes configurar un PIN de retiro');

        $this->service->requestWithdraw($wallet, 50.0, $dir['directoryId'], $dir['directoryType'], withdrawalPin: '123456');
    }

    // ── Lockout tras intentos fallidos ────────────────────────────────────────

    public function test_wrong_pin_five_times_locks_out_even_with_correct_pin_after() : void
    {
        $wallet = $this->makeWalletWithUser(1000.0, pin: '123456');
        $dir = $this->makeDirectory($wallet);

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->service->requestWithdraw($wallet, 50.0, $dir['directoryId'], $dir['directoryType'], withdrawalPin: '000000');
            } catch (WalletDomainException $e) {
                // esperado en cada iteración
            }
        }

        // El 6to intento, incluso con el PIN CORRECTO, debe bloquearse por
        // lockout — checkWithdrawalPinLockout() corre ANTES de Hash::check().
        $this->expectException(WalletDomainException::class);
        $this->expectExceptionMessage('Demasiados intentos');

        $this->service->requestWithdraw($wallet, 50.0, $dir['directoryId'], $dir['directoryType'], withdrawalPin: '123456');
    }

    private function makeDirectory(Wallet $wallet) : array
    {
        // Usar un destino persistido y propiedad del usuario: requestWithdraw()
        // valida titularidad y estado ACTIVE dentro de la transacción.
        $directory = BinanceAccount::create([
            'account_type' => 'B',
            'data_value' => 'usuario-binance-' . $wallet->user_id,
            'user_id' => $wallet->user_id,
            'status' => 'ACTIVE',
        ]);

        return ['directoryId' => (string) $directory->id, 'directoryType' => 'binance'];
    }

    private function makeWalletWithUser(float $balance = 1000.0, ?string $pin = null) : Wallet
    {
        $user = User::factory()->create();
        if ($pin !== null) {
            $user->forceFill([
                'withdrawal_pin_hash' => Hash::make($pin),
                'withdrawal_pin_set_at' => now(),
            ])->save();
        }

        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        // requestWithdraw() -> WalletLimitService::assertWithinLimits() exige
        // un WalletLimit configurado (bloquea por precaución si no existe) —
        // sin esto, cualquier retiro falla ANTES de llegar al check del PIN
        // real que este test quiere ejercitar.
        WalletLimit::firstOrCreate(
            ['currency_id' => null, 'kyc_level' => 0],
            [
                'min_deposit' => 1, 'max_deposit' => 100000,
                'min_per_transaction' => 1, 'max_per_transaction' => 100000,
                'max_daily' => 100000, 'max_weekly' => 100000, 'max_monthly' => 100000,
                'is_active' => true,
            ],
        );
        WalletLimit::forgetCatalogCache();

        $this->actingAs($user);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => $balance,
            'bonus_balance' => 0,
            'status' => 'ACTIVE',
        ]);

        return $wallet->load('user');
    }
}
