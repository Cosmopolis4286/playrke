<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
 * Comercio/agencia: ven todo lo de SUS salas + lo vendido por SU red en
 * salas ajenas, nunca las ventas de otros comercios (antes: "sus salas + todas
 * las PUBLIC"). Reportes a nivel sala solo sobre salas propias.
 *
 * Jugadores externos: solo se reconocen/reutilizan dentro de la red comercial
 * que los captó.
 *
 * Escenario compartido:
 *  - Comercio C con sala R1; otro comercio D con sala R2.
 *  - Vendedores A y B de C venden en R1; vendedor E de D vende en R2.
 *  - Agencia S de C con vendedor F, que vende en R2 (sala de D).
 *  - A tiene además un lote REFUNDED en R1 (excluido de totales de venta).
 */
class SellerBingoReportScopeTest extends TestCase
{
    use DatabaseTransactions;

    private static int $idCounter = 0;

    /** @var array<string, string[]> cartones por vendedor (clave = nombre) */
    private array $cards = [];
    private User $commerceC;
    private User $commerceD;

    private Country $country;
    private Currency $currency;

    /** @var array<string, string> premio por vendedor */
    private array $prizes = [];

    private string $roomR1;
    private string $roomR2;

    private User $sellerA;
    private User $sellerB;
    private User $sellerE;
    private User $sellerF;
    private User $subsidiaryS;

    protected function setUp() : void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->currency = Currency::firstOrCreate(
            ['iso_code' => 'USD'],
            ['symbol' => '$', 'name' => 'US Dollar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE'],
        );
        $this->country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO', 'status' => 'ACTIVE'],
        );

        $this->seedRolesAndPermissions();

        $this->commerceC = $this->makeCommerce();
        $this->commerceD = $this->makeCommerce();

        $this->sellerA = $this->makeSeller($this->commerceC);
        $this->sellerB = $this->makeSeller($this->commerceC);
        $this->sellerE = $this->makeSeller($this->commerceD);

        [$this->subsidiaryS, $subsidiaryId] = $this->makeSubsidiary($this->commerceC);
        // manager_user_id = Commerce raíz (misma convención que
        // ManagerSellerService); la agencia va en subsidiary_id.
        $this->sellerF = $this->makeSeller($this->commerceC, $subsidiaryId);

        $this->roomR1 = $this->makeRoom($this->commerceC);
        $this->roomR2 = $this->makeRoom($this->commerceD);

        // A: 2 cartones ($10 c/u, comisión 0.30 c/u) + lote REFUNDED de 1 cartón.
        $this->sell('A', $this->sellerA, $this->roomR1, cardCount: 2, saleAmount: 20.00, commission: 0.60);
        $this->sell('A', $this->sellerA, $this->roomR1, cardCount: 1, saleAmount: 10.00, commission: 0.30, status: 'REFUNDED');
        // B (mismo comercio que A) y E (otro comercio).
        $this->sell('B', $this->sellerB, $this->roomR1, cardCount: 3, saleAmount: 30.00, commission: 0.90);
        $this->sell('E', $this->sellerE, $this->roomR2, cardCount: 4, saleAmount: 40.00, commission: 1.20);
        // F (agencia S de C) vende en la sala de D.
        $this->sell('F', $this->sellerF, $this->roomR2, cardCount: 2, saleAmount: 20.00, commission: 0.60);

        $this->prize('A', $this->sellerA, $this->roomR1);
        $this->prize('B', $this->sellerB, $this->roomR1);
        $this->prize('E', $this->sellerE, $this->roomR2);
        $this->prize('F', $this->sellerF, $this->roomR2);
    }

    protected function tearDown() : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    /** @test */
    public function commerce_cannot_open_room_detail_of_another_commerce() : void
    {
        $this->actingAs($this->commerceC)
            ->getJson('/bingo/sales-report-room-data/' . $this->roomHash($this->roomR2))
            ->assertForbidden();
    }

    // ─── Comercio / Agencia: red propia, nunca otros comercios ──────────────

    /** @test */
    public function commerce_cards_report_includes_own_rooms_and_network_sales_elsewhere_but_not_other_commerces() : void
    {
        $response = $this->actingAs($this->commerceC)->getJson('/bingo/cards-sold-data?per_page=100');

        $response->assertOk();

        // A y B (su sala R1) + F (su agencia, vendiendo en la sala de D) — nunca E.
        $this->assertEqualsCanonicalizing(
            array_merge($this->cards['A'], $this->cards['B'], $this->cards['F']),
            collect($response->json('cards.rows'))->pluck('id')->all(),
        );
    }

    /** @test */
    public function commerce_sales_report_only_lists_own_rooms() : void
    {
        $response = $this->actingAs($this->commerceC)->getJson('/bingo/sales-report-data?per_page=100');

        $response->assertOk();

        $roomKeys = collect($response->json('sales.rows'))
            ->map(fn (array $row) => explode(':', $row['id'])[0])
            ->unique()
            ->values()
            ->all();

        // R2 no aparece aunque F (su red) vendió ahí: la economía de R2 es de D.
        $this->assertSame([$this->roomHash($this->roomR1)], $roomKeys);
        $this->assertSame(1, $response->json('sales.summary.total_rooms'));
    }

    // ─── Comercio (sin cambios de scope + fix de hash) ──────────────────────

    /** @test */
    public function commerce_sales_report_row_ids_use_route_key_so_room_detail_resolves() : void
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

    /** @test */
    public function commerce_winners_report_excludes_other_commerces() : void
    {
        $response = $this->actingAs($this->commerceC)->getJson('/bingo/winners-data?per_page=100');

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            [$this->prizes['A'], $this->prizes['B'], $this->prizes['F']],
            collect($response->json('winners.rows'))->pluck('id')->all(),
        );
    }

    // ─── Jugadores externos: solo dentro de la red que los captó ─────────────

    /** @test */
    public function external_player_check_only_finds_players_of_the_same_commercial_network() : void
    {
        $phone = '+584141234567';
        $this->makeExternalPlayer($this->sellerE, $this->roomR2, $phone);

        $query = http_build_query([
            'phone' => $phone,
            'country_id' => (string) $this->country->id,
            'game_id' => '1',
            'room_id' => $this->roomHash($this->roomR2),
        ]);

        // A (comercio C) comparte la sala pero no la red: no debe ver al comprador de E.
        $this->actingAs($this->sellerA)->getJson('/external-players/check?' . $query)
            ->assertOk()
            ->assertJson(['exists' => false, 'player' => null]);

        // E (quien lo captó) sí lo reconoce.
        $this->actingAs($this->sellerE)->getJson('/external-players/check?' . $query)
            ->assertOk()
            ->assertJson(['exists' => true]);
    }

    /** @test */
    public function external_player_is_shared_across_sellers_of_the_same_commerce() : void
    {
        $phone = '+584149876543';
        $this->makeExternalPlayer($this->sellerA, $this->roomR1, $phone);

        $query = http_build_query([
            'phone' => $phone,
            'country_id' => (string) $this->country->id,
            'game_id' => '1',
            'room_id' => $this->roomHash($this->roomR1),
        ]);

        // F es de una agencia de C: misma red comercial que A.
        $this->actingAs($this->sellerF)->getJson('/external-players/check?' . $query)
            ->assertOk()
            ->assertJson(['exists' => true]);
    }

    /** @test */
    public function room_owner_sees_all_activity_in_own_room_including_other_networks() : void
    {
        // D es dueño de R2: ve lo de su vendedor E y también lo que vendió F
        // (red de C) en SU sala — es la actividad de su propia sala.
        $response = $this->actingAs($this->commerceD)->getJson('/bingo/cards-sold-data?per_page=100');

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            array_merge($this->cards['E'], $this->cards['F']),
            collect($response->json('cards.rows'))->pluck('id')->all(),
        );
    }

    /** @test */
    public function seller_cannot_open_room_detail_where_they_have_no_sales() : void
    {
        $this->actingAs($this->sellerA)
            ->getJson('/bingo/sales-report-room-data/' . $this->roomHash($this->roomR2))
            ->assertForbidden();
    }

    // ─── Cartones ────────────────────────────────────────────────────────────

    /** @test */
    public function seller_only_sees_own_cards_in_cards_sold_report() : void
    {
        $response = $this->actingAs($this->sellerA)->getJson('/bingo/cards-sold-data?per_page=100');

        $response->assertOk();
        $ids = collect($response->json('cards.rows'))->pluck('id')->all();

        // Incluye el cartón del lote REFUNDED (se lista con estado "Refunded").
        $this->assertEqualsCanonicalizing($this->cards['A'], $ids);
    }

    // ─── Ganadores ───────────────────────────────────────────────────────────

    /** @test */
    public function seller_only_sees_winners_of_own_cards() : void
    {
        $response = $this->actingAs($this->sellerA)->getJson('/bingo/winners-data?per_page=100');

        $response->assertOk();
        $this->assertSame([$this->prizes['A']], collect($response->json('winners.rows'))->pluck('id')->all());
    }

    // ─── Ventas por Sala ─────────────────────────────────────────────────────

    /** @test */
    public function seller_room_detail_shows_only_own_cards_and_own_commission() : void
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

    // ─── Reporte de Ventas ───────────────────────────────────────────────────

    /** @test */
    public function seller_sales_report_aggregates_only_own_non_refunded_lots() : void
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

    /** @test */
    public function subsidiary_only_sees_sales_of_its_own_sellers() : void
    {
        $cards = $this->actingAs($this->subsidiaryS)->getJson('/bingo/cards-sold-data?per_page=100');
        $cards->assertOk();
        $this->assertEqualsCanonicalizing($this->cards['F'], collect($cards->json('cards.rows'))->pluck('id')->all());

        // No es dueña de ninguna sala: el reporte a nivel sala queda vacío.
        $sales = $this->actingAs($this->subsidiaryS)->getJson('/bingo/sales-report-data?per_page=100');
        $sales->assertOk();
        $this->assertSame([], $sales->json('sales.rows'));
    }

    private function makeCommerce() : User
    {
        $user = $this->makeUserWithRole('commerce');
        $uniqueCode = UniqueCode::create([
            'rke_code' => 'RKE-' . $this->nextId(),
            'uuid_code' => (string) Str::uuid(),
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        Commerce::create([
            'name' => 'Comercio ' . $user->id,
            'country_id' => $this->country->id,
            'phone' => '+58414' . random_int(1000000, 9999999),
            'user_id' => $user->id,
            'unique_code_id' => $uniqueCode->id,
            'commission' => 5.00,
            'status' => 'ACTIVE',
        ]);

        return $user;
    }

    private function makeExternalPlayer(User $seller, string $roomId, string $phone) : void
    {
        DB::table('external_players')->insert([
            'id' => $this->nextId(),
            'link' => Str::random(28),
            'game_id' => 1,
            'room_id' => $roomId,
            'creator_user_id' => $seller->id,
            'country_id' => $this->country->id,
            'currency_id' => $this->currency->id,
            'seller_user_id' => $seller->id,
            'purchase_code_id' => $this->nextId(),
            'first_name' => 'Comprador',
            'last_name' => 'Externo',
            'phone' => $phone,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeRoom(User $creator) : string
    {
        $id = $this->nextId();

        DB::table('bingo_rooms')->insert([
            'id' => $id,
            'uuid_code' => (string) Str::uuid(),
            'status_id' => $this->nextId(),
            'creator_user_id' => $creator->id,
            // Métricas agregadas de la sala (vista comercio): todas las ventas.
            'metrics_by_currency' => json_encode([
                (string) $this->currency->id => ['total_sales' => 100, 'total_cards' => 10, 'rke_commission' => 5, 'room_commission' => 5],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ambas salas PUBLIC a propósito: es exactamente el caso que filtraba
        // el scope anterior (BingoRoom::scopeAccessibleBy = propias + TODAS
        // las PUBLIC) — sin esto los tests de aislamiento pasarían también
        // con el código viejo.
        DB::table('bingo_rooms_config')->insert([
            'id' => $this->nextId(),
            'room_id' => $id,
            'room_type_id' => $this->publicRoomTypeId(),
            'creator_user_id' => $creator->id,
            'connection_type_id' => $this->nextId(),
            'prize_pool_type_id' => $this->nextId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function makeSeller(User $manager, ?string $subsidiaryId = null) : User
    {
        $user = $this->makeUserWithRole('seller');
        $sellerId = $this->nextId();

        Seller::create([
            'id' => $sellerId,
            'name' => 'Vendedor ' . $user->id,
            'phone' => '+58424' . random_int(1000000, 9999999),
            'commission' => 3.00,
            'user_id' => $user->id,
            'country_id' => $this->country->id,
            'manager_user_id' => $manager->id,
            'status' => 'ACTIVE',
        ]);

        // Directo en BD: no depende de que subsidiary_id esté en $fillable.
        if ($subsidiaryId !== null) {
            DB::table('sellers')->where('id', $sellerId)->update(['subsidiary_id' => $subsidiaryId]);
        }

        return $user;
    }

    /** @return array{0: User, 1: string} [usuario de la agencia, subsidiaries.id] */
    private function makeSubsidiary(User $commerceUser) : array
    {
        $user = $this->makeUserWithRole('subsidiary');
        $id = $this->nextId();

        DB::table('subsidiaries')->insert([
            'id' => $id,
            'name' => 'Agencia ' . $id,
            'commission' => 2.00,
            'commerce_id' => Commerce::where('user_id', $commerceUser->id)->value('id'),
            'user_id' => $user->id,
            'country_id' => $this->country->id,
            'currency_id' => $this->currency->id,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $id];
    }

    private function makeUserWithRole(string $role) : User
    {
        $user = User::factory()->create(['country_id' => $this->country->id]);
        $user->assignRole($role);

        return $user;
    }

    private function nextId() : string
    {
        return (string) (410000000000000000 + (++self::$idCounter));
    }

    private function prize(string $key, User $seller, string $roomId) : void
    {
        $prizeId = $this->nextId();
        $cardId = $this->cards[$key][0];

        DB::table('bingo_card_prizes')->insert([
            'id' => $prizeId,
            'room_id' => $roomId,
            'creator_user_id' => $seller->id,
            'seller_user_id' => $seller->id,
            'seller_type' => 'SELLER',
            'room_user_id' => $this->nextId(),
            'card_lot_id' => DB::table('bingo_cards')->where('id', $cardId)->value('card_lot_id'),
            'winning_card_id' => $cardId,
            'currency_id' => $this->currency->id,
            'round_id' => $this->nextId(),
            'status' => 'ACTIVE',
            'prize_type' => 'full_house',
            'winning_pattern' => 'Cartón lleno',
            'usd_amount' => 50,
            'local_amount' => 50,
            'prize_status' => 'waiting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->prizes[$key] = $prizeId;
    }

    private function publicRoomTypeId() : string
    {
        $existing = DB::table('room_types')->where('code', 'PUBLIC')->value('id');
        if ($existing) {
            return (string) $existing;
        }

        $id = $this->nextId();
        DB::table('room_types')->insert([
            'id' => $id, 'code' => 'PUBLIC', 'label' => 'Pública', 'status' => 'ACTIVE',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function roomHash(string $roomId) : string
    {
        return BingoRoom::findOrFail($roomId)->getRouteKey();
    }

    // ─── Fixtures ────────────────────────────────────────────────────────────

    private function seedRolesAndPermissions() : void
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

        foreach (['commerce', 'seller', 'subsidiary'] as $name) {
            $role = Role::where('name', $name)->where('guard_name', 'web')->first();
            if (! $role) {
                // App\Models\Role (SnowflakeId, no autoincrement) — el Role de
                // Spatie pisaría el id con lastInsertId (0) al guardar y los
                // permisos quedarían colgados de role_id = 0.
                $role = (new Role)->forceFill(['name' => $name, 'guard_name' => 'web', 'prefix' => $name]);
                $role->save();
            }
            $role->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function sell(string $key, User $seller, string $roomId, int $cardCount, float $saleAmount, float $commission, string $status = 'ACTIVE') : void
    {
        $lotId = $this->nextId();

        DB::table('bingo_card_lots')->insert([
            'id' => $lotId,
            'room_id' => $roomId,
            'user_id' => $seller->id,
            'seller_user_id' => $seller->id,
            'seller_type' => 'SELLER',
            'room_user_id' => $this->nextId(),
            'currency_id' => $this->currency->id,
            'card_count' => $cardCount,
            'card_price' => $saleAmount / $cardCount,
            'exchange_rate' => 1,
            'sale_amount' => $saleAmount,
            'commission_amount' => $commission,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 0; $i < $cardCount; $i++) {
            $cardId = $this->nextId();

            DB::table('bingo_cards')->insert([
                'id' => $cardId,
                'status' => $status === 'REFUNDED' ? BingoCard::STATUS_CANCELLED : BingoCard::STATUS_ACTIVE,
                'room_id' => $roomId,
                'creator_user_id' => $seller->id,
                'seller_user_id' => $seller->id,
                'seller_type' => 'SELLER',
                'room_user_id' => $this->nextId(),
                'card_lot_id' => $lotId,
                'currency_id' => $this->currency->id,
                'serial_number' => 'T' . $cardId,
                'card_numbers' => json_encode([]),
                'card_hash' => hash('sha256', $cardId),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->cards[$key][] = $cardId;
        }
    }
}
