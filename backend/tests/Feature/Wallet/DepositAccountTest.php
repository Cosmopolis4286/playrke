<?php

namespace Tests\Feature\Wallet;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletLimit;
use App\Domains\Wallet\Models\WalletMovement;
use App\Http\Middleware\RequireTosAcceptance;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\Platform\DepositAccount;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * D1: cuentas de la plataforma donde depositar. El admin las gestiona, el
 * jugador ve las activas de su moneda y, si existen, indica a cuál pagó.
 */
class DepositAccountTest extends TestCase
{
    use RefreshDatabase;

    private Currency $currency;

    protected function setUp() : void
    {
        parent::setUp();
        (new MovementTypeSeeder)->run();
        MovementType::forgetCatalogCache();
        foreach (['wallet.view', 'wallet.deposit', 'wallet.movements.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $this->currency = Currency::firstOrCreate(['iso_code' => 'VES'], ['name' => 'Bolívar', 'type' => 'FIAT']);

        // Aceptar TOS no es lo que se prueba aquí.
        $this->withoutMiddleware(RequireTosAcceptance::class);
        Storage::fake('local');

        // Sin límites configurados la moneda no acepta depósitos.
        WalletLimit::create([
            'currency_id' => $this->currency->id, 'kyc_level' => 0,
            'min_deposit' => 1, 'max_deposit' => 1000, 'min_per_transaction' => 1, 'max_per_transaction' => 500,
            'max_daily' => 1000, 'max_weekly' => 3000, 'max_monthly' => 10000, 'is_active' => true,
        ]);
    }

    public function test_deposit_requires_choosing_an_account_when_the_currency_has_them() : void
    {
        $account = $this->account(['label' => 'Banesco PM', 'account_identifier' => '0414-0000000']);
        $player = $this->player();
        Wallet::create(['user_id' => $player->id, 'currency_id' => $this->currency->id, 'balance' => 0, 'bonus_balance' => 0, 'status' => 'ACTIVE']);

        $this->actingAs($player)->post('/wallet/deposit', $this->depositPayload(), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('deposit_account_id');

        $this->actingAs($player)->post('/wallet/deposit', $this->depositPayload() + ['deposit_account_id' => (string) $account->id], ['Accept' => 'application/json'])
            ->assertOk();

        $movement = WalletMovement::where('user_id', $player->id)->where('type', WalletMovement::TYPE_DEPOSIT)->sole();
        $this->assertSame('Banesco PM', $movement->metadata['deposit_account']['label']);
        $this->assertSame('0414-0000000', $movement->metadata['deposit_account']['account_identifier']);
    }

    public function test_deposit_without_accounts_configured_still_works() : void
    {
        $player = $this->player();
        Wallet::create(['user_id' => $player->id, 'currency_id' => $this->currency->id, 'balance' => 0, 'bonus_balance' => 0, 'status' => 'ACTIVE']);

        $this->actingAs($player)->post('/wallet/deposit', $this->depositPayload(), ['Accept' => 'application/json'])
            ->assertOk();
    }

    public function test_only_movement_managers_can_manage_accounts() : void
    {
        $payload = [
            'currency_id' => (string) $this->currency->id, 'method' => 'transferencia', 'label' => 'Mercantil',
            'holder_name' => 'PlayRKE C.A.', 'account_identifier' => '0105-0000-00-0000000000', 'status' => 'ACTIVE',
        ];

        $this->actingAs($this->player())->postJson('/deposit-accounts', $payload)->assertForbidden();

        $admin = User::factory()->create();
        $admin->givePermissionTo('wallet.movements.manage');
        $this->actingAs($admin)->postJson('/deposit-accounts', $payload)->assertOk();
        $this->actingAs($admin)->postJson('/deposit-accounts', ['method' => 'nope'] + $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('method');

        $this->assertSame(1, DepositAccount::count());
    }

    public function test_player_sees_only_active_accounts_of_the_currency() : void
    {
        $active = $this->account(['label' => 'Banesco PM']);
        $this->account(['label' => 'Inactiva', 'status' => 'INACTIVE']);
        $other = Currency::firstOrCreate(['iso_code' => 'COP'], ['name' => 'Peso', 'type' => 'FIAT']);
        $this->account(['label' => 'Otra moneda', 'currency_id' => (string) $other->id]);

        $this->actingAs($this->player())
            ->getJson('/wallet/deposit-accounts?currency_id=' . $this->currency->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (string) $active->id)
            ->assertJsonPath('data.0.method_label', 'Pago móvil');
    }

    private function account(array $overrides = []) : DepositAccount
    {
        return DepositAccount::create($overrides + [
            'currency_id' => (string) $this->currency->id,
            'method' => 'pago_movil',
            'label' => 'Cuenta',
            'holder_name' => 'PlayRKE C.A.',
            'account_identifier' => '0414-1234567',
            'status' => 'ACTIVE',
        ]);
    }

    private function depositPayload() : array
    {
        return [
            'currency_id' => (string) $this->currency->id,
            'amount' => 50,
            'receipt' => UploadedFile::fake()->image('comprobante.jpg'),
        ];
    }

    private function player() : User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo(['wallet.view', 'wallet.deposit']);

        return $user;
    }
}
