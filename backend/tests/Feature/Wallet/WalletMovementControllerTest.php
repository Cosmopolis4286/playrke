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

    protected function setUp() : void
    {
        parent::setUp();

        (new MovementTypeSeeder)->run();
        MovementType::forgetCatalogCache();

        $this->makeRole('admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('wallet.movements.view');
        $this->admin->givePermissionTo('wallet.movements.manage');

        $this->player = User::factory()->create();
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        $this->wallet = Wallet::create([
            'user_id' => $this->player->id,
            'currency_id' => $currency->id,
            'balance' => 100.0,
            'bonus_balance' => 0,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_can_approve_pending_deposit() : void
    {
        $movement = $this->makePendingDeposit(50.0);

        $this->actingAs($this->admin)
            ->postJson(route('wallet-movements.approve', $movement))
            ->assertOk()
            ->assertJsonPath('data.status', WalletMovement::STATUS_COMPLETED);

        $this->assertSame(150.0, (float) $this->wallet->fresh()->balance);
    }

    public function test_admin_can_reject_pending_deposit() : void
    {
        $movement = $this->makePendingDeposit(50.0);

        $this->actingAs($this->admin)
            ->postJson(route('wallet-movements.reject', $movement), ['admin_notes' => 'Pago no recibido'])
            ->assertOk()
            ->assertJsonPath('data.status', WalletMovement::STATUS_REJECTED)
            ->assertJsonPath('data.admin_notes', 'Pago no recibido');

        // Un depósito rechazado nunca tocó el balance al crearse, y
        // rechazarlo tampoco debe moverlo.
        $this->assertSame(100.0, (float) $this->wallet->fresh()->balance);
    }

    public function test_admin_can_view_movements_index() : void
    {
        $this->actingAs($this->admin)
            ->get(route('wallet-movements.index'))
            ->assertOk();
    }

    public function test_approving_an_already_completed_movement_fails() : void
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

    public function test_bulk_approve_processes_each_item_and_reports_missing_ids() : void
    {
        $first = $this->makePendingDeposit(10.0);
        $second = $this->makePendingDeposit(20.0);

        $response = $this->actingAs($this->admin)
            ->postJson(route('wallet-movements.bulk-approve'), [
                'ids' => [(string) $first->id, (string) $second->id, '999999999999999999'],
            ])
            ->assertOk();

        $response->assertJsonPath('updated', 2);
        $this->assertCount(1, $response->json('skipped'));
        $this->assertSame('999999999999999999', $response->json('skipped.0.id'));

        $this->assertSame(130.0, (float) $this->wallet->fresh()->balance);
    }

    public function test_bulk_reject_requires_a_reason() : void
    {
        $movement = $this->makePendingDeposit();

        $this->actingAs($this->admin)
            ->postJson(route('wallet-movements.bulk-reject'), ['ids' => [(string) $movement->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('admin_notes');
    }

    public function test_csv_export_includes_related_user_data() : void
    {
        $this->makePendingDeposit();

        $response = $this->actingAs($this->admin)->get(route('wallet-movements.export'));
        $response->assertOk();

        $this->assertStringContainsString($this->player->email, $response->streamedContent());
    }

    public function test_manager_index_exposes_can_manage() : void
    {
        $this->actingAs($this->admin)->get(route('wallet-movements.index'))
            ->assertInertia(fn ($page) => $page->where('canManage', true));
    }

    public function test_non_admin_cannot_approve_movement() : void
    {
        $movement = $this->makePendingDeposit();

        $this->actingAs($this->player)
            ->postJson(route('wallet-movements.approve', $movement))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_view_movements_index() : void
    {
        // MultiPermissionMiddleware redirige a dashboard (no 403) cuando el
        // request no pide JSON — es una ruta Inertia, no un endpoint JSON.
        $this->actingAs($this->player)
            ->get(route('wallet-movements.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_reject_requires_a_reason() : void
    {
        $movement = $this->makePendingDeposit();

        $this->actingAs($this->admin)
            ->postJson(route('wallet-movements.reject', $movement))
            ->assertStatus(422)
            ->assertJsonValidationErrors('admin_notes');

        $this->actingAs($this->admin)
            ->postJson(route('wallet-movements.reject', $movement), ['admin_notes' => 'no'])
            ->assertStatus(422);

        $this->assertSame(WalletMovement::STATUS_PENDING, $movement->fresh()->status);
    }

    public function test_search_matches_reference_and_movement_id_suffix() : void
    {
        $withRef = $this->makePendingDeposit();
        $withRef->update(['reference_id' => 'REF-778899']);
        $other = $this->makePendingDeposit();

        $byRef = $this->actingAs($this->admin)
            ->getJson(route('wallet-movements.fetch', ['search' => '778899']))
            ->assertOk()
            ->json('data');
        $this->assertSame([(string) $withRef->id], array_column($byRef, 'id'));

        $suffix = substr((string) $other->id, -8);
        $byId = $this->actingAs($this->admin)
            ->getJson(route('wallet-movements.fetch', ['search' => $suffix]))
            ->assertOk()
            ->json('data');
        $this->assertContains((string) $other->id, array_column($byId, 'id'));
    }

    public function test_stats_include_queue_sla() : void
    {
        $old = $this->makePendingDeposit();
        $old->forceFill(['created_at' => now()->subHours(30)])->save();
        $this->makePendingDeposit();

        $this->actingAs($this->admin)
            ->getJson(route('wallet-movements.stats'))
            ->assertOk()
            ->assertJsonPath('data.pending_count', 2)
            ->assertJsonPath('data.critical_pending_count', 1)
            ->assertJsonStructure(['data' => ['oldest_pending_at']]);
    }

    public function test_treasurer_can_approve_pending_deposit() : void
    {
        // Tesorería gestiona la cola de recargas/retiros (rol en
        // WalletMovementPolicy + wallet.movements.manage en la ruta).
        $this->makeRole('treasurer');
        $treasurer = User::factory()->create();
        $treasurer->assignRole('treasurer');
        $treasurer->givePermissionTo(['wallet.movements.view', 'wallet.movements.manage']);

        $movement = $this->makePendingDeposit(50.0);

        $this->actingAs($treasurer)
            ->postJson(route('wallet-movements.approve', $movement))
            ->assertOk()
            ->assertJsonPath('data.status', WalletMovement::STATUS_COMPLETED);

        $this->assertSame(150.0, (float) $this->wallet->fresh()->balance);
    }

    public function test_view_only_admin_can_list_but_cannot_mutate() : void
    {
        $viewer = $this->makeViewOnlyAdmin();
        $movement = $this->makePendingDeposit();

        $this->actingAs($viewer)->get(route('wallet-movements.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canManage', false));

        $this->actingAs($viewer)->postJson(route('wallet-movements.approve', $movement))->assertForbidden();
        $this->actingAs($viewer)->postJson(route('wallet-movements.reject', $movement), ['admin_notes' => 'Motivo válido'])->assertForbidden();
        $this->actingAs($viewer)->putJson(route('wallet-movements.assign', $movement))->assertForbidden();
        $this->actingAs($viewer)->postJson(route('wallet-movements.bulk-approve'), ['ids' => [(string) $movement->id]])->assertForbidden();

        $this->assertSame(WalletMovement::STATUS_PENDING, $movement->fresh()->status);
        $this->assertSame(100.0, (float) $this->wallet->fresh()->balance);
    }

    private function makePendingDeposit(float $amount = 50.0) : WalletMovement
    {
        return WalletMovement::create([
            'wallet_id' => $this->wallet->id,
            'user_id' => $this->player->id,
            'type' => WalletMovement::TYPE_DEPOSIT,
            'status' => WalletMovement::STATUS_PENDING,
            'amount' => $amount,
            'balance_before' => 100.0,
            'balance_after' => 100.0,
        ]);
    }

    private function makeRole(string $name) : void
    {
        if (Role::where('name', $name)->where('guard_name', 'web')->exists()) {
            return;
        }

        $role = new Role(['name' => $name, 'guard_name' => 'web', 'prefix' => strtoupper($name), 'type' => 'web']);
        $role->id = Role::generateSnowflakeId();
        $role->save();
    }

    private function makeViewOnlyAdmin() : User
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('admin');
        $viewer->givePermissionTo('wallet.movements.view');

        return $viewer;
    }
}
