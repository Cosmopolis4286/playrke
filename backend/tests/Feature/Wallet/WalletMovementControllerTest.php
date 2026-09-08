<?php

namespace Tests\Feature\Wallet;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletMovementControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $player;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        (new MovementTypeSeeder())->run();
        MovementType::forgetCatalogCache();

        $this->makeRole('admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('wallet.movements.view');
        $this->admin->givePermissionTo('wallet.movements.manage');

        $this->player = User::factory()->create();
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        $this->wallet = Wallet::create([
            'user_id'       => $this->player->id,
            'currency_id'   => $currency->id,
            'balance'       => 100.0,
            'bonus_balance' => 0,
            'status'        => 'ACTIVE',
        ]);
    }

    private function makeRole(string $name): void
    {
        if (Role::where('name', $name)->where('guard_name', 'web')->exists()) {
            return;
        }

        $role = new Role(['name' => $name, 'guard_name' => 'web', 'prefix' => strtoupper($name), 'type' => 'web']);
        $role->id = Role::generateSnowflakeId();
        $role->save();
    }

    private function makePendingDeposit(float $amount = 50.0): WalletMovement
    {
        return WalletMovement::create([
            'wallet_id'      => $this->wallet->id,
            'user_id'        => $this->player->id,
            'type'           => WalletMovement::TYPE_DEPOSIT,
            'status'         => WalletMovement::STATUS_PENDING,
            'amount'         => $amount,
            'balance_before' => 100.0,
            'balance_after'  => 100.0,
        ]);
    }

    public function test_non_admin_cannot_view_movements_index(): void
    {
        // MultiPermissionMiddleware redirige a dashboard (no 403) cuando el
        // request no pide JSON — es una ruta Inertia, no un endpoint JSON.
        $this->actingAs($this->player)
            ->get(route('wallet-movements.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_movements_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('wallet-movements.index'))
            ->assertOk();
    }

    public function test_admin_can_approve_pending_deposit(): void
    {
        $movement = $this->makePendingDeposit(50.0);

        $this->actingAs($this->admin)
            ->postJson(route('wallet-movements.approve', $movement))
            ->assertOk()
            ->assertJsonPath('data.status', WalletMovement::STATUS_COMPLETED);

        $this->assertSame(150.0, (float) $this->wallet->fresh()->balance);
    }

    public function test_admin_can_reject_pending_deposit(): void
    {
        $movement = $this->makePendingDeposit(50.0);

        $this->actingAs($this->admin)
            ->postJson(route('wallet-movements.reject', $movement))
            ->assertOk()
            ->assertJsonPath('data.status', WalletMovement::STATUS_REJECTED);

        // Un depósito rechazado nunca tocó el balance al crearse, y
        // rechazarlo tampoco debe moverlo.
        $this->assertSame(100.0, (float) $this->wallet->fresh()->balance);
    }

    public function test_non_admin_cannot_approve_movement(): void
    {
        $movement = $this->makePendingDeposit();

        $this->actingAs($this->player)
            ->postJson(route('wallet-movements.approve', $movement))
            ->assertForbidden();
    }

    public function test_approving_an_already_completed_movement_fails(): void
    {
        $movement = $this->makePendingDeposit();
        $this->actingAs($this->admin)->postJson(route('wallet-movements.approve', $movement));

        // WalletMovementPolicy::approve() ya exige status===PENDING, así que
        // un segundo intento SECUENCIAL sobre el mismo movimiento (ya
        // COMPLETED) es rechazado por la Policy con 403 antes de llegar al
        // Service — nunca dispara approvalRequiresPendingStatus() (409),
        // que es la defensa de segunda línea para el caso de dos requests
        // CONCURRENTES pasando la Policy casi al mismo tiempo (cubierto en
        // WalletServiceTest::test_approve_deposit_twice_throws_...).
        $response = $this->actingAs($this->admin)
            ->postJson(route('wallet-movements.approve', $movement->fresh()));

        $response->assertForbidden();
    }
}
