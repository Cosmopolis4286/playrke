<?php

namespace Tests\Feature\Roles\Seller;

use App\DTOs\Roles\Admin\Subsidiary\AdminSubsidiaryDTO;
use App\DTOs\Roles\Seller\Manager\ManagerSellerDTO;
use App\Exceptions\Domain\Roles\CommissionHierarchyException;
use App\Exceptions\Domain\Roles\Seller\Manager\ManagerSellerException;
use App\Services\Roles\CommissionHierarchyGuard;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Role;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use App\Services\Roles\Admin\Subsidiary\AdminSubsidiaryCrudService;
use App\Services\Roles\Seller\Manager\ManagerSellerService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Integridad de la jerarquía Comercializadora → Agencia → Vendedor al
 * asignar/reasignar vendedores y al mover agencias:
 *
 * - IDOR en ManagerSellerService::create(): el manager_user_id explícito se
 *   aceptaba sin validar contra el actor — un commerce podía crear sellers
 *   dentro de la red de OTRO comercio (o de una agencia ajena).
 * - Reasignación en update(): un commerce solo puede mover su seller entre
 *   sí mismo y sus propias agencias.
 * - 'customer' ya no es un manager válido (sellers que no podía operar ni
 *   podían vender).
 * - Mover una agencia a otro Commerce resincroniza sellers.manager_user_id
 *   (antes quedaban contados en la red del Commerce anterior).
 */
class ManagerSellerHierarchyTest extends TestCase
{
    use DatabaseTransactions;

    private static int $idCounter = 0;

    private Country $country;
    private Currency $currency;

    private function nextId(): string
    {
        return (string) (430000000000000000 + (++self::$idCounter));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::firstOrCreate(
            ['iso_code' => 'USD'],
            ['symbol' => '$', 'name' => 'US Dollar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE']
        );
        $this->country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO', 'status' => 'ACTIVE']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['admin', 'commerce', 'subsidiary', 'seller', 'customer'] as $name) {
            if (!Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                // App\Models\Role (SnowflakeId) — ver
                // feedback-tests-roles-snowflake-y-corridas-paralelas.
                (new Role())->forceFill(['name' => $name, 'guard_name' => 'web', 'prefix' => $name])->save();
            }
        }
    }

    // ─── Fixtures ────────────────────────────────────────────────────────────

    private function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create(['country_id' => $this->country->id]);
        $user->assignRole($role);

        return $user;
    }

    /** @return array{0: User, 1: Commerce} */
    private function makeCommerce(): array
    {
        $user = $this->makeUserWithRole('commerce');

        $commerce = Commerce::create([
            'name'           => 'Comercio ' . $user->id,
            'country_id'     => $this->country->id,
            'phone'          => '+58414' . random_int(1000000, 9999999),
            'user_id'        => $user->id,
            'unique_code_id' => UniqueCode::create([
                'rke_code'  => 'RKE-' . $this->nextId(),
                'uuid_code' => (string) Str::uuid(),
                'user_id'   => $user->id,
                'status'    => 'ACTIVE',
            ])->id,
            'commission'     => 5.00,
            'status'         => 'ACTIVE',
        ]);

        return [$user, $commerce];
    }

    /** @return array{0: User, 1: Subsidiary} */
    private function makeSubsidiary(Commerce $commerce): array
    {
        $user = $this->makeUserWithRole('subsidiary');

        $subsidiary = Subsidiary::create([
            'name'        => 'Agencia ' . $user->id,
            'commerce_id' => $commerce->id,
            'user_id'     => $user->id,
            'country_id'  => $this->country->id,
            'currency_id' => $this->currency->id,
            // Topes vendedor (3) ≤ agencia (4) ≤ comercializadora (5).
            'commission'  => 4.00,
            'phone'       => '+58414' . random_int(1000000, 9999999),
            'status'      => 'ACTIVE',
        ]);

        return [$user, $subsidiary];
    }

    /** Seller con los mismos datos que persiste ManagerSellerService. */
    private function makeSeller(Commerce $commerce, ?Subsidiary $subsidiary = null): Seller
    {
        $user = $this->makeUserWithRole('seller');

        return Seller::create([
            'id'              => $this->nextId(),
            'name'            => 'Vendedor ' . $user->id,
            'phone'           => '+58424' . random_int(1000000, 9999999),
            'commission'      => 3.00,
            'user_id'         => $user->id,
            'country_id'      => $this->country->id,
            'manager_user_id' => $commerce->user_id,
            'subsidiary_id'   => $subsidiary?->id,
            'status'          => 'ACTIVE',
        ]);
    }

    private function dto(?string $managerUserId = null, ?string $commission = null): ManagerSellerDTO
    {
        return new ManagerSellerDTO(
            username: null,
            email: null,
            commission: $commission,
            country: null,
            timeZone: null,
            currency: null,
            phone: null,
            name: null,
            otherCurrencies: null,
            allowedGames: null,
            managerUserId: $managerUserId,
        );
    }

    private function service(): ManagerSellerService
    {
        return app(ManagerSellerService::class);
    }

    // ─── create(): destino explícito validado contra el actor ────────────────

    public function test_commerce_cannot_create_seller_under_another_commerce(): void
    {
        [$ownUser] = $this->makeCommerce();
        [$foreignUser] = $this->makeCommerce();

        $this->actingAs($ownUser);

        $this->expectException(ManagerSellerException::class);

        try {
            $this->service()->create($this->dto((string) $foreignUser->id));
        } finally {
            $this->assertSame(0, Seller::where('manager_user_id', $foreignUser->id)->count());
        }
    }

    public function test_commerce_cannot_create_seller_under_another_commerces_subsidiary(): void
    {
        [$ownUser] = $this->makeCommerce();
        [, $foreignCommerce] = $this->makeCommerce();
        [$foreignSubsidiaryUser, $foreignSubsidiary] = $this->makeSubsidiary($foreignCommerce);

        $this->actingAs($ownUser);

        $this->expectException(ManagerSellerException::class);

        try {
            $this->service()->create($this->dto((string) $foreignSubsidiaryUser->id));
        } finally {
            $this->assertSame(0, Seller::where('subsidiary_id', $foreignSubsidiary->id)->count());
        }
    }

    public function test_customer_cannot_create_sellers(): void
    {
        $customer = $this->makeUserWithRole('customer');

        $this->actingAs($customer);

        $this->expectException(ManagerSellerException::class);

        try {
            $this->service()->create($this->dto());
        } finally {
            $this->assertSame(0, Seller::where('manager_user_id', $customer->id)->count());
        }
    }

    // ─── update(): reasignación dentro del propio árbol ──────────────────────

    public function test_commerce_can_move_own_seller_to_own_subsidiary(): void
    {
        [$ownUser, $ownCommerce] = $this->makeCommerce();
        [$subsidiaryUser, $subsidiary] = $this->makeSubsidiary($ownCommerce);
        $seller = $this->makeSeller($ownCommerce);

        $this->actingAs($ownUser);

        $this->service()->update($seller, $this->dto((string) $subsidiaryUser->id));

        $seller->refresh();
        $this->assertSame((string) $subsidiary->id, (string) $seller->subsidiary_id);
        // manager_user_id sigue siendo el Commerce raíz, nunca la agencia.
        $this->assertSame((string) $ownUser->id, (string) $seller->manager_user_id);
    }

    public function test_commerce_cannot_move_own_seller_to_foreign_subsidiary(): void
    {
        [$ownUser, $ownCommerce] = $this->makeCommerce();
        [, $foreignCommerce] = $this->makeCommerce();
        [$foreignSubsidiaryUser] = $this->makeSubsidiary($foreignCommerce);
        $seller = $this->makeSeller($ownCommerce);

        $this->actingAs($ownUser);

        $this->expectException(ManagerSellerException::class);

        try {
            $this->service()->update($seller, $this->dto((string) $foreignSubsidiaryUser->id));
        } finally {
            $seller->refresh();
            $this->assertNull($seller->subsidiary_id);
            $this->assertSame((string) $ownUser->id, (string) $seller->manager_user_id);
        }
    }

    public function test_subsidiary_cannot_reassign_its_seller(): void
    {
        [, $commerce] = $this->makeCommerce();
        [$subsidiaryUser, $subsidiary] = $this->makeSubsidiary($commerce);
        $seller = $this->makeSeller($commerce, $subsidiary);

        $this->actingAs($subsidiaryUser);

        // Intento de "subir" el seller al Commerce padre: se ignora.
        $this->service()->update($seller, $this->dto((string) $commerce->user_id));

        $seller->refresh();
        $this->assertSame((string) $subsidiary->id, (string) $seller->subsidiary_id);
    }

    // ─── Topes de comisión: vendedor ≤ agencia ≤ comercializadora ────────────

    public function test_commerce_cannot_create_seller_above_its_own_commission(): void
    {
        [$ownUser] = $this->makeCommerce(); // 5%

        $this->actingAs($ownUser);

        $this->expectException(CommissionHierarchyException::class);

        $this->service()->create($this->dto(commission: '6'));
    }

    public function test_seller_cannot_be_moved_to_an_agency_with_lower_commission(): void
    {
        [$ownUser, $ownCommerce] = $this->makeCommerce();
        [$subsidiaryUser, $subsidiary] = $this->makeSubsidiary($ownCommerce); // 4%
        $seller = $this->makeSeller($ownCommerce);
        $seller->update(['commission' => 4.50]);

        $this->actingAs($ownUser);

        $this->expectException(CommissionHierarchyException::class);

        try {
            $this->service()->update($seller, $this->dto((string) $subsidiaryUser->id));
        } finally {
            $this->assertNull($seller->fresh()->subsidiary_id);
        }
    }

    public function test_seller_commission_cannot_exceed_its_agency_on_update(): void
    {
        [$ownUser, $ownCommerce] = $this->makeCommerce();
        [, $subsidiary] = $this->makeSubsidiary($ownCommerce); // 4%
        $seller = $this->makeSeller($ownCommerce, $subsidiary);

        $this->actingAs($ownUser);

        $this->expectException(CommissionHierarchyException::class);

        $this->service()->update($seller, $this->dto(commission: '4.5'));
    }

    public function test_agency_and_commerce_rates_respect_their_network(): void
    {
        [, $commerce] = $this->makeCommerce();                 // 5%
        [, $subsidiary] = $this->makeSubsidiary($commerce);    // 4%
        $this->makeSeller($commerce, $subsidiary);             // 3%

        $guard = app(CommissionHierarchyGuard::class);

        // Válidos: dentro de los topes.
        $guard->assertSubsidiaryRate(3.0, (string) $commerce->id, (string) $subsidiary->id);
        $guard->assertCommerceRate(4.0, $commerce);

        $cases = [
            'agencia sobre su comercializadora' => fn () => $guard->assertSubsidiaryRate(5.5, (string) $commerce->id, (string) $subsidiary->id),
            'agencia bajo su vendedor'          => fn () => $guard->assertSubsidiaryRate(2.5, (string) $commerce->id, (string) $subsidiary->id),
            'comercializadora bajo su agencia'  => fn () => $guard->assertCommerceRate(3.5, $commerce),
        ];

        foreach ($cases as $label => $case) {
            try {
                $case();
                $this->fail("Debió rechazar: {$label}");
            } catch (CommissionHierarchyException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    // ─── Mover una agencia de Commerce ───────────────────────────────────────

    public function test_moving_subsidiary_to_another_commerce_resyncs_its_sellers(): void
    {
        [$oldUser, $oldCommerce] = $this->makeCommerce();
        [$newUser, $newCommerce] = $this->makeCommerce();
        [, $subsidiary] = $this->makeSubsidiary($oldCommerce);

        $active = $this->makeSeller($oldCommerce, $subsidiary);
        $trashed = $this->makeSeller($oldCommerce, $subsidiary);
        $trashed->delete();
        $direct = $this->makeSeller($oldCommerce);

        app(AdminSubsidiaryCrudService::class)->update(
            $subsidiary,
            AdminSubsidiaryDTO::fromArray(['commerce' => (string) $newCommerce->id])
        );

        $this->assertSame((string) $newCommerce->id, (string) $subsidiary->fresh()->commerce_id);
        $this->assertSame((string) $newUser->id, (string) $active->fresh()->manager_user_id);
        $this->assertSame((string) $newUser->id, (string) Seller::withTrashed()->find($trashed->id)->manager_user_id);
        // Un seller directo del Commerce anterior no se toca.
        $this->assertSame((string) $oldUser->id, (string) $direct->fresh()->manager_user_id);

        // La red del Commerce anterior ya no incluye a los sellers de la agencia.
        $this->assertNotContains((string) $active->user_id, $oldCommerce->fresh()->networkUserIds());
        $this->assertContains((string) $active->user_id, $newCommerce->fresh()->networkUserIds());
    }
}
