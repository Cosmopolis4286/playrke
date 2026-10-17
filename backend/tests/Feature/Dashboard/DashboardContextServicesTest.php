<?php

namespace Tests\Feature\Dashboard;

use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Roles\Admin;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use App\Services\Roles\Admin\Dashboard\AdminDashboardContextService;
use App\Services\Roles\Commerce\Dashboard\CommerceDashboardContextService;
use App\Services\Roles\Player\Dashboard\PlayerDashboardContextService;
use App\Services\Roles\Seller\Dashboard\SellerDashboardContextService;
use App\Services\Roles\SuperAdmin\Dashboard\SuperAdminDashboardContextService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cobertura de regresión para los 5 *DashboardContextService — antes de
 * esta suite ninguno tenía test, y fue exactamente ese punto ciego (una
 * excepción silenciada en ContextResolverService) el que dejó los
 * dashboards de Commerce y Seller sin datos reales durante mucho tiempo sin
 * que nada lo detectara. Cada test solo confirma que dashboardContext()
 * resuelve sin excepción y que toView() trae las keys esperadas — no
 * valida el contenido/lógica de negocio de cada campo, ver los tests de
 * dominio correspondientes (BingoTest, etc.) para eso.
 */
class DashboardContextServicesTest extends TestCase
{
    use DatabaseTransactions;

    protected Country $country;
    protected Currency $currency;

    private static int $idCounter = 0;

    private function nextId(): string
    {
        return (string) (400000000000000000 + (++self::$idCounter));
    }

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->seedRoles();

        $this->currency = Currency::firstOrCreate(
            ['iso_code' => 'USD'],
            ['symbol' => '$', 'name' => 'US Dollar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE']
        );

        $this->country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO', 'status' => 'ACTIVE']
        );
    }

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    // ─── Fixtures compartidas ────────────────────────────────────────────────

    private function seedRoles(): void
    {
        foreach (['superadmin', 'admin', 'commerce', 'seller', 'player'] as $name) {
            if (!Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                $role = new Role([
                    'name' => $name,
                    'guard_name' => 'web',
                    'prefix' => $name,
                    'type' => 'web',
                ]);
                $role->id = $this->nextId();
                $role->save();
            }
        }
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create(['country_id' => $this->country->id]);
        $user->assignRole($role);

        return $user;
    }

    private function makeUniqueCode(User $user): UniqueCode
    {
        return UniqueCode::create([
            'rke_code'  => 'RKE-' . $this->nextId(),
            'uuid_code' => (string) \Illuminate\Support\Str::uuid(),
            'user_id'   => $user->id,
            'status'    => 'ACTIVE',
        ]);
    }

    private function makeAdmin(): User
    {
        $user = $this->makeUserWithRole('admin');
        $uniqueCode = $this->makeUniqueCode($user);

        Admin::create([
            'user_id'        => $user->id,
            'unique_code_id' => $uniqueCode->id,
            'country_id'     => $this->country->id,
            'currency_id'    => $this->currency->id,
            'status'         => 'ACTIVE',
        ]);

        return $user;
    }

    private function makeCommerce(): User
    {
        $user = $this->makeUserWithRole('commerce');
        $uniqueCode = $this->makeUniqueCode($user);

        Commerce::create([
            'name'           => 'Comercio de prueba',
            'country_id'     => $this->country->id,
            'phone'          => '+58414' . random_int(1000000, 9999999),
            'user_id'        => $user->id,
            'unique_code_id' => $uniqueCode->id,
            'commission'     => 5.00,
            'status'         => 'ACTIVE',
        ]);

        return $user;
    }

    private function makeSeller(User $manager): User
    {
        $user = $this->makeUserWithRole('seller');

        Seller::create([
            'id'              => $this->nextId(),
            'name'            => 'Vendedor de prueba',
            'phone'           => '+58424' . random_int(1000000, 9999999),
            'commission'      => 3.00,
            'user_id'         => $user->id,
            'country_id'      => $this->country->id,
            'manager_user_id' => $manager->id,
            'status'          => 'ACTIVE',
        ]);

        return $user;
    }

    // ─── SuperAdmin ─────────────────────────────────────────────────────────

    /** @test */
    public function superadmin_dashboard_context_resolves_with_expected_keys(): void
    {
        $superadmin = $this->makeUserWithRole('superadmin');
        Auth::login($superadmin);

        $view = app(SuperAdminDashboardContextService::class)->dashboardContext()->toView();

        $this->assertEqualsCanonicalizing(
            ['userName', 'stats', 'recentActivity', 'adminActivity', 'entityCounts', 'systemHealth', 'salesTrend', 'pendingWalletCount', 'allowedQuickLinks', 'period'],
            array_keys($view)
        );
    }

    // ─── Admin ──────────────────────────────────────────────────────────────

    /** @test */
    public function admin_dashboard_context_resolves_with_expected_keys(): void
    {
        $admin = $this->makeAdmin();
        Auth::login($admin);

        $view = app(AdminDashboardContextService::class)->dashboardContext()->toView();

        $this->assertEqualsCanonicalizing(
            ['userName', 'stats', 'recentActivity', 'entityCounts', 'salesTrend', 'rkeTotals', 'pendingWalletCount', 'allowedQuickLinks', 'period'],
            array_keys($view)
        );
    }

    // ─── Commerce ───────────────────────────────────────────────────────────

    /** @test */
    public function commerce_dashboard_context_resolves_with_expected_keys(): void
    {
        $commerceUser = $this->makeCommerce();
        Auth::login($commerceUser);

        $view = app(CommerceDashboardContextService::class)->dashboardContext()->toView();

        $this->assertEqualsCanonicalizing(
            ['userName', 'stats', 'recentActivity', 'entityCounts', 'salesTrend', 'salesBySeller', 'wallets', 'configuration', 'pendingWalletCount', 'period', 'rkeReceivables'],
            array_keys($view)
        );
    }

    // ─── Seller ─────────────────────────────────────────────────────────────

    /** @test */
    public function seller_dashboard_context_resolves_with_expected_keys(): void
    {
        $commerceUser = $this->makeCommerce();
        $sellerUser   = $this->makeSeller($commerceUser);
        Auth::login($sellerUser);

        $view = app(SellerDashboardContextService::class)->dashboardContext()->toView();

        $this->assertEqualsCanonicalizing(
            ['userName', 'commission', 'stats', 'recentActivity', 'reportsTo', 'roomCommissions', 'salesTrend', 'ranking', 'period', 'pendingActions', 'upcomingRooms'],
            array_keys($view)
        );

        // reportsTo debe resolver al Commerce recién creado (reporte directo, sin agencia).
        $this->assertSame('commerce', $view['reportsTo']['type']);
        $this->assertSame('Comercio de prueba', $view['reportsTo']['commerce_name']);

        // Vendedor nuevo: las tres colas existen, vacías.
        $this->assertSame(['in_review', 'rejected', 'prizes'], array_column($view['pendingActions'], 'key'));
        $this->assertSame([0, 0, 0], array_column($view['pendingActions'], 'count'));
        $this->assertSame([], $view['upcomingRooms']);
    }

    /** @test */
    public function seller_pending_actions_count_only_own_payments_by_status(): void
    {
        $commerceUser = $this->makeCommerce();
        $sellerUser   = $this->makeSeller($commerceUser);
        $otherSeller  = $this->makeSeller($commerceUser);

        $payment = fn (User $seller, string $status, float $amount, ?string $processedAt = null) => BingoCardPayment::create([
            'payment_method'        => 'mobile_payment',
            'room_id'               => '1',
            'creator_user_id'       => $commerceUser->id,
            'seller_user_id'        => $seller->id,
            'currency_id'           => $this->currency->id,
            'card_lot_id'           => '1',
            'payment_amount'        => $amount,
            'payment_proof'         => 'private/receipts/bingo/test.png',
            'payment_status'        => $status,
            'processed_at'          => $processedAt,
            'transaction_reference' => uniqid('REF'),
        ]);

        $payment($sellerUser, 'PENDING', 10);
        $payment($sellerUser, 'PENDING', 5);
        $payment($sellerUser, 'REJECTED', 7, now()->subDays(2)->toDateTimeString());
        $payment($sellerUser, 'REJECTED', 9, now()->subDays(30)->toDateTimeString()); // fuera de los 7 días
        $payment($sellerUser, 'APPROVED', 20, now()->toDateTimeString());
        $payment($otherSeller, 'PENDING', 99);                                         // de otro vendedor

        Auth::login($sellerUser);
        $actions = collect(app(SellerDashboardContextService::class)->dashboardContext()->toView()['pendingActions'])
            ->keyBy('key');

        $this->assertSame(2, $actions['in_review']['count']);
        $this->assertSame(trim($this->currency->symbol . ' ' . $this->currency->formatAmount(15)), $actions['in_review']['amount']);
        $this->assertSame(1, $actions['rejected']['count']);
        $this->assertSame(0, $actions['prizes']['count']);
        $this->assertNull($actions['prizes']['amount']);
    }

    // ─── Player ─────────────────────────────────────────────────────────────

    /** @test */
    public function player_dashboard_context_resolves_with_expected_keys(): void
    {
        $player = $this->makeUserWithRole('player');
        Auth::login($player);

        $view = app(PlayerDashboardContextService::class)->dashboardContext()->toView();

        $this->assertEqualsCanonicalizing(
            ['userName', 'stats', 'recentActivity', 'prizesWon', 'triviaActivity', 'kycStatus', 'showCrossGameBridge', 'period'],
            array_keys($view)
        );

        // Jugador recién creado, sin documento KYC enviado.
        $this->assertNull($view['kycStatus']);
    }
}
