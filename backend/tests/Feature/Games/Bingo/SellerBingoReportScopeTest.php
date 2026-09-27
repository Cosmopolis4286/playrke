<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Scope de vendedor en BingoReportController (Cartones / Ganadores /
 * Reporte de Ventas / Ventas por Sala): el seller ve SOLO sus ventas
 * (seller_user_id), nunca las de otro vendedor del mismo comercio ni las de
 * salas ajenas — antes el scope era "salas del comercio + todas las PUBLIC",
 * lo que habría expuesto ventas, jugadores y comisiones de terceros al
 * concederle games.bingo.sales_report/winners.
 *
 * Escenario compartido:
 *  - Comercio C con sala R1; otro comercio D con sala R2.
 *  - Vendedores A y B de C venden en R1; vendedor E de D vende en R2.
 *  - A tiene además un lote REFUNDED en R1 (excluido de totales de venta).
 */
class SellerBingoReportScopeTest extends TestCase
{
    use DatabaseTransactions;

    private static int $idCounter = 0;

    private Country $country;
    private Currency $currency;

    private User $sellerA;
    private User $sellerB;
    private User $sellerE;
    private User $commerceC;

    private string $roomR1;
    private string $roomR2;

    /** @var array<string, string[]> cartones por vendedor (clave = nombre) */
    private array $cards = [];

    /** @var array<string, string> premio por vendedor */
    private array $prizes = [];

    private function nextId(): string
    {
        return (string) (410000000000000000 + (++self::$idCounter));
    }

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->currency = Currency::firstOrCreate(
            ['iso_code' => 'USD'],
            ['symbol' => '$', 'name' => 'US Dollar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE']
        );
        $this->country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO', 'status' => 'ACTIVE']
        );

        $this->seedRolesAndPermissions();

        $this->commerceC = $this->makeCommerce();
        $commerceD       = $this->makeCommerce();

        $this->sellerA = $this->makeSeller($this->commerceC);
        $this->sellerB = $this->makeSeller($this->commerceC);
        $this->sellerE = $this->makeSeller($commerceD);

        $this->roomR1 = $this->makeRoom($this->commerceC);
        $this->roomR2 = $this->makeRoom($commerceD);

        // A: 2 cartones ($10 c/u, comisión 0.30 c/u) + lote REFUNDED de 1 cartón.
        $this->sell('A', $this->sellerA, $this->roomR1, cardCount: 2, saleAmount: 20.00, commission: 0.60);
        $this->sell('A', $this->sellerA, $this->roomR1, cardCount: 1, saleAmount: 10.00, commission: 0.30, status: 'REFUNDED');
        // B (mismo comercio que A) y E (otro comercio).
        $this->sell('B', $this->sellerB, $this->roomR1, cardCount: 3, saleAmount: 30.00, commission: 0.90);
        $this->sell('E', $this->sellerE, $this->roomR2, cardCount: 4, saleAmount: 40.00, commission: 1.20);

        $this->prize('A', $this->sellerA, $this->roomR1);
        $this->prize('B', $this->sellerB, $this->roomR1);
        $this->prize('E', $this->sellerE, $this->roomR2);
    }

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    // ─── Fixtures ────────────────────────────────────────────────────────────

    private function seedRolesAndPermissions(): void
    {
        // Limpiar ANTES de asignar: el cache de Spatie puede traer IDs de
        // permisos creados por otros tests y ya revertidos (DatabaseTransactions)
        // — givePermissionTo() los resolvería desde ahí y, con FK checks
        // apagados, adjuntaría IDs inexistentes sin error.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = ['games.bingo', 'games.bingo.sales_report', 'games.bingo.winners'];
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['commerce', 'seller'] as $name) {
            $role = Role::where('name', $name)->where('guard_name', 'web')->first();
            if (!$role) {
                // App\Models\Role (SnowflakeId, no autoincrement) — el Role de
                // Spatie pisaría el id con lastInsertId (0) al guardar y los
                // permisos quedarían colgados de role_id = 0.
                $role = (new Role())->forceFill(['name' => $name, 'guard_name' => 'web', 'prefix' => $name]);
                $role->save();
            }
            $role->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create(['country_id' => $this->country->id]);
        $user->assignRole($role);

        return $user;
    }

    private function makeCommerce(): User
    {
        $user = $this->makeUserWithRole('commerce');
        $uniqueCode = UniqueCode::create([
            'rke_code'  => 'RKE-' . $this->nextId(),
            'uuid_code' => (string) Str::uuid(),
            'user_id'   => $user->id,
            'status'    => 'ACTIVE',
        ]);

        Commerce::create([
            'name'           => 'Comercio ' . $user->id,
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
            'name'            => 'Vendedor ' . $user->id,
            'phone'           => '+58424' . random_int(1000000, 9999999),
            'commission'      => 3.00,
            'user_id'         => $user->id,
            'country_id'      => $this->country->id,
            'manager_user_id' => $manager->id,
            'status'          => 'ACTIVE',
        ]);

        return $user;
    }

    private function makeRoom(User $creator): string
    {
        $id = $this->nextId();

        DB::table('bingo_rooms')->insert([
            'id'              => $id,
            'uuid_code'       => (string) Str::uuid(),
            'status_id'       => $this->nextId(),
            'creator_user_id' => $creator->id,
            // Métricas agregadas de la sala (vista comercio): todas las ventas.
            'metrics_by_currency' => json_encode([
                (string) $this->currency->id => ['total_sales' => 100, 'total_cards' => 10, 'rke_commission' => 5, 'room_commission' => 5],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function sell(string $key, User $seller, string $roomId, int $cardCount, float $saleAmount, float $commission, string $status = 'ACTIVE'): void
    {
        $lotId = $this->nextId();

        DB::table('bingo_card_lots')->insert([
            'id'                => $lotId,
            'room_id'           => $roomId,
            'user_id'           => $seller->id,
            'seller_user_id'    => $seller->id,
            'seller_type'       => 'SELLER',
            'room_user_id'      => $this->nextId(),
            'currency_id'       => $this->currency->id,
            'card_count'        => $cardCount,
            'card_price'        => $saleAmount / $cardCount,
            'exchange_rate'     => 1,
            'sale_amount'       => $saleAmount,
            'commission_amount' => $commission,
            'status'            => $status,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        for ($i = 0; $i < $cardCount; $i++) {
            $cardId = $this->nextId();

            DB::table('bingo_cards')->insert([
                'id'              => $cardId,
                'status'          => $status === 'REFUNDED' ? BingoCard::STATUS_CANCELLED : BingoCard::STATUS_ACTIVE,
                'room_id'         => $roomId,
                'creator_user_id' => $seller->id,
                'seller_user_id'  => $seller->id,
                'seller_type'     => 'SELLER',
                'room_user_id'    => $this->nextId(),
                'card_lot_id'     => $lotId,
                'currency_id'     => $this->currency->id,
                'serial_number'   => 'T' . $cardId,
                'card_numbers'    => json_encode([]),
                'card_hash'       => hash('sha256', $cardId),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $this->cards[$key][] = $cardId;
        }
    }

    private function prize(string $key, User $seller, string $roomId): void
    {
        $prizeId = $this->nextId();
        $cardId  = $this->cards[$key][0];

        DB::table('bingo_card_prizes')->insert([
            'id'              => $prizeId,
            'room_id'         => $roomId,
            'creator_user_id' => $seller->id,
            'seller_user_id'  => $seller->id,
            'seller_type'     => 'SELLER',
            'room_user_id'    => $this->nextId(),
            'card_lot_id'     => DB::table('bingo_cards')->where('id', $cardId)->value('card_lot_id'),
            'winning_card_id' => $cardId,
            'currency_id'     => $this->currency->id,
            'round_id'        => $this->nextId(),
            'status'          => 'ACTIVE',
            'prize_type'      => 'full_house',
            'winning_pattern' => 'Cartón lleno',
            'usd_amount'      => 50,
            'local_amount'    => 50,
            'prize_status'    => 'waiting',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->prizes[$key] = $prizeId;
    }

    private function roomHash(string $roomId): string
    {
        return BingoRoom::findOrFail($roomId)->getRouteKey();
    }

    // ─── Cartones ────────────────────────────────────────────────────────────

    /** @test */
    public function seller_only_sees_own_cards_in_cards_sold_report(): void
    {
        $response = $this->actingAs($this->sellerA)->getJson('/bingo/cards-sold-data?per_page=100');

        $response->assertOk();
        $ids = collect($response->json('cards.rows'))->pluck('id')->all();

        // Incluye el cartón del lote REFUNDED (se lista con estado "Refunded").
        $this->assertEqualsCanonicalizing($this->cards['A'], $ids);
    }

    // ─── Ganadores ───────────────────────────────────────────────────────────

    /** @test */
    public function seller_only_sees_winners_of_own_cards(): void
    {
        $response = $this->actingAs($this->sellerA)->getJson('/bingo/winners-data?per_page=100');

        $response->assertOk();
        $this->assertSame([$this->prizes['A']], collect($response->json('winners.rows'))->pluck('id')->all());
    }

    // ─── Reporte de Ventas ───────────────────────────────────────────────────

    /** @test */
    public function seller_sales_report_aggregates_only_own_non_refunded_lots(): void
    {
        $response = $this->actingAs($this->sellerA)->getJson('/bingo/sales-report-data?per_page=100');

        $response->assertOk();

        $rows = $response->json('sales.rows');
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame($this->roomHash($this->roomR1) . ':' . $this->currency->id, $row['id']);
        $this->assertSame(2, $row['total_cards_sold']);
        $this->assertEqualsWithDelta(20.00, $row['total_amount'], 0.001);
        // Su propia comisión (commission_amount del lote), no la de sala/plataforma.
        $this->assertEqualsWithDelta(0.60, $row['commission'], 0.001);
        $this->assertEqualsWithDelta(19.40, $row['net_amount'], 0.001);

        $summary = $response->json('sales.summary');
        $this->assertSame(1, $summary['total_rooms']);
        $this->assertSame(2, $summary['total_cards_sold']);
        $this->assertEqualsWithDelta(0.60, $summary['total_commission'], 0.001);

        $labels = collect($response->json('sales.columns'))->pluck('label', 'key');
        $this->assertSame('Mi Comisión', $labels['commission']);
        $this->assertSame('Total Banca', $labels['net_amount']);
    }

    // ─── Ventas por Sala ─────────────────────────────────────────────────────

    /** @test */
    public function seller_room_detail_shows_only_own_cards_and_own_commission(): void
    {
        $response = $this->actingAs($this->sellerA)
            ->getJson('/bingo/sales-report-room-data/' . $this->roomHash($this->roomR1) . '?per_page=100');

        $response->assertOk();

        $ids = collect($response->json('roomSales.rows'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing($this->cards['A'], $ids);

        $info = $response->json('roomSales.room_info');
        $this->assertSame(2, $info['total_cards_sold']);
        $this->assertEqualsWithDelta(20.00, $info['total_amount'], 0.001);
        $this->assertEqualsWithDelta(0.60, $info['commission'], 0.001);
    }

    /** @test */
    public function seller_cannot_open_room_detail_where_they_have_no_sales(): void
    {
        $this->actingAs($this->sellerA)
            ->getJson('/bingo/sales-report-room-data/' . $this->roomHash($this->roomR2))
            ->assertForbidden();
    }

    // ─── Comercio (sin cambios de scope + fix de hash) ──────────────────────

    /** @test */
    public function commerce_sales_report_row_ids_use_route_key_so_room_detail_resolves(): void
    {
        $response = $this->actingAs($this->commerceC)->getJson('/bingo/sales-report-data?per_page=100');

        $response->assertOk();

        $roomKeys = collect($response->json('sales.rows'))
            ->map(fn (array $row) => explode(':', $row['id'])[0])
            ->unique()
            ->values()
            ->all();

        $this->assertContains($this->roomHash($this->roomR1), $roomKeys);

        // El comercio sigue viendo todas las ventas de su sala (A y B).
        $detail = $this->actingAs($this->commerceC)
            ->getJson('/bingo/sales-report-room-data/' . $this->roomHash($this->roomR1) . '?per_page=100');

        $detail->assertOk();
        $this->assertEqualsCanonicalizing(
            array_merge($this->cards['A'], $this->cards['B']),
            collect($detail->json('roomSales.rows'))->pluck('id')->all(),
        );
    }
}
