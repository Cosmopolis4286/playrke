<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Venta de cartones por vendedor (POST /bingo-card/{room}/generate) y
 * revisión del pago — fase 1 de la auditoría del flujo:
 *
 *  - El precio y la comisión de la vista previa son EXACTAMENTE los que se
 *    registran (antes: tasa oficial vs tasa propia del comercio, y comisión
 *    base vs comisión especial de la sala).
 *  - La venta queda con pago PENDING real (antes el INSERT fallaba siempre y
 *    los cartones quedaban jugables sin revisión), comprobante en disco privado.
 *  - Precio cambiado, comprobante duplicado, sala privada ajena y moneda no
 *    habilitada se rechazan.
 *  - Solo el comercio de la red (o admin) revisa el pago; el vendedor nunca.
 *
 * Escenario: comercio C (tasa propia 2.5 para la moneda TSD) con vendedor A;
 * comercio D con vendedor E. Sala R1 de C (USD 10 → 25 TSD). Sala privada de D.
 */
class SellerCardSaleTest extends TestCase
{
    use DatabaseTransactions;

    private static int $idCounter = 0;

    private Country $country;
    private Currency $currency;

    private User $commerceC;
    private User $commerceD;
    private User $sellerA;
    private User $sellerE;
    private User $admin;

    private BingoRoom $roomR1;
    private BingoRoom $privateRoomD;

    private const COMMERCE_RATE = 2.5;
    private const ROOM_PRICE_USD = 10.00;

    private function nextId(): string
    {
        return (string) (420000000000000000 + (++self::$idCounter));
    }

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Storage::fake('local');
        Cache::flush();

        $this->seedGameStatuses();
        \App\Models\Game::firstOrCreate(['name' => 'BINGO'], ['status' => 'ACTIVE']);

        $this->country = Country::firstOrCreate(
            ['iso_code_2' => 'TS'],
            ['name' => 'Test Sale Country', 'iso_code_3' => 'TSC', 'status' => 'ACTIVE']
        );
        $this->currency = Currency::firstOrCreate(
            ['iso_code' => 'TSD'],
            ['symbol' => 'T$', 'name' => 'Test Dollar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE']
        );
        DB::table('currencies')->where('id', $this->currency->id)->update(['country_id' => $this->country->id]);

        $this->seedRolesAndPermissions();

        $this->admin     = $this->makeUserWithRole('admin');
        $this->commerceC = $this->makeCommerce(ownRate: self::COMMERCE_RATE);
        $this->commerceD = $this->makeCommerce();
        $this->sellerA   = $this->makeSeller($this->commerceC);
        $this->sellerE   = $this->makeSeller($this->commerceD);

        $this->roomR1       = $this->makeRoom($this->commerceC, 'PUBLIC');
        $this->privateRoomD = $this->makeRoom($this->commerceD, 'PRIVATE');
    }

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    // ─── Fixtures ────────────────────────────────────────────────────────────

    private function seedGameStatuses(): void
    {
        GameStatus::forgetCache();

        foreach (['ACTIVE', 'INACTIVE', 'DELETED', 'CLOSED', 'IN_PROGRESS', 'FINISHED', 'REFUND', 'OFFLINE'] as $code) {
            if (!GameStatus::where('code', $code)->exists()) {
                GameStatus::create(['code' => $code, 'label' => $code]);
            }
        }
    }

    private function seedRolesAndPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $grants = [
            'admin'    => ['games.manage', 'games.bingo', 'games.bingo.manage'],
            'commerce' => ['games.bingo', 'games.bingo.manage', 'games.bingo.game_rooms'],
            'seller'   => ['games.bingo', 'games.bingo.game_rooms'],
        ];

        foreach (array_unique(array_merge(...array_values($grants))) as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach ($grants as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (!$role) {
                $role = (new Role())->forceFill(['name' => $roleName, 'guard_name' => 'web', 'prefix' => $roleName]);
                $role->save();
            }
            $role->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create(['country_id' => $this->country->id, 'email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    private function makeCommerce(?float $ownRate = null): User
    {
        $user = $this->makeUserWithRole('commerce');
        $uniqueCode = UniqueCode::create([
            'rke_code'  => 'RKE-' . $this->nextId(),
            'uuid_code' => (string) Str::uuid(),
            'user_id'   => $user->id,
            'status'    => 'ACTIVE',
        ]);

        $commerce = Commerce::create([
            'name'           => 'Comercio ' . $user->id,
            'country_id'     => $this->country->id,
            'phone'          => '+58414' . random_int(1000000, 9999999),
            'user_id'        => $user->id,
            'unique_code_id' => $uniqueCode->id,
            'commission'     => 5.00,
            'status'         => 'ACTIVE',
        ]);

        DB::table('commerces_currencies')->insert([
            'commerce_id' => $commerce->id, 'currency_id' => $this->currency->id, 'status' => 'ACTIVE',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        if ($ownRate !== null) {
            DB::table('commerces_exchange_rates')->insert([
                'id' => $this->nextId(), 'commerce_id' => $commerce->id, 'currency_id' => $this->currency->id,
                'amount' => $ownRate, 'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $user;
    }

    private function makeSeller(User $commerceUser): User
    {
        $user = $this->makeUserWithRole('seller');
        $sellerId = $this->nextId();

        Seller::create([
            'id'              => $sellerId,
            'name'            => 'Vendedor ' . $user->id,
            'phone'           => '+58424' . random_int(1000000, 9999999),
            'commission'      => 3.00,
            'user_id'         => $user->id,
            'country_id'      => $this->country->id,
            'manager_user_id' => $commerceUser->id,
            'status'          => 'ACTIVE',
        ]);

        DB::table('sellers_currencies')->insert([
            'seller_id' => $sellerId, 'currency_id' => $this->currency->id, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $user;
    }

    private function roomTypeId(string $code): string
    {
        $existing = DB::table('room_types')->where('code', $code)->value('id');
        if ($existing) {
            return (string) $existing;
        }

        $id = $this->nextId();
        DB::table('room_types')->insert([
            'id' => $id, 'code' => $code, 'label' => $code, 'status' => 'ACTIVE',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function makeRoom(User $creator, string $roomType): BingoRoom
    {
        $roomId = $this->nextId();

        DB::table('bingo_rooms')->insert([
            'id' => $roomId, 'uuid_code' => (string) Str::uuid(),
            'status_id' => GameStatus::codeToId('ACTIVE'), 'creator_user_id' => $creator->id,
            'paid_prizes' => 'N', 'prize_pool' => 0, 'full_house_prize' => 0, 'serial_prize' => 0,
            'rke_commission' => 0, 'room_commission' => 0, 'seller_commission' => 0,
            'total_players' => 0, 'total_cards' => 0, 'total_sales' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('bingo_rooms_config')->insert([
            'id' => $this->nextId(), 'room_id' => $roomId, 'template_id' => null, 'parent_automatic_id' => null,
            'is_scheduled' => 'N', 'from_house' => 'N', 'room_type_id' => $this->roomTypeId($roomType),
            'creator_user_id' => $creator->id, 'connection_type_id' => $this->nextId(), 'in_live' => 'N',
            'date' => now()->addDay()->toDateString(), 'time' => now()->format('H:i:s'),
            'unix_date' => now()->addDay()->timestamp, 'use_base_usd' => 1,
            'card_price_usd' => self::ROOM_PRICE_USD,
            'allowed_currencies' => json_encode([(string) $this->currency->id]),
            'prize_pool_type_id' => $this->nextId(), 'rounds' => '1', 'use_serials' => 0, 'status' => 'ACTIVE',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return BingoRoom::findOrFail($roomId);
    }

    private function sale(User $seller, BingoRoom $room, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($seller)->post('/bingo-card/' . $room->getRouteKey() . '/generate', array_merge([
            'card_count'          => 2,
            'country_id'          => (string) $this->country->id,
            'player'              => ['phone' => '+584141112233', 'first_name' => 'Ana', 'last_name' => 'Pérez'],
            'receipt_number'      => 'REF-' . Str::random(6),
            'receipt_image'       => UploadedFile::fake()->image('comprobante.jpg'),
            'expected_card_price' => self::ROOM_PRICE_USD * self::COMMERCE_RATE,
        ], $overrides), ['Accept' => 'application/json']);
    }

    // ─── Precio y comisión: vista previa == venta ────────────────────────────

    /** @test */
    public function seller_preview_uses_commerce_rate_and_room_commission_and_matches_recorded_sale(): void
    {
        // Comisión especial de A en R1 (distinta de su base 3%).
        DB::table('sellers_room_limits')->insert([
            'id' => $this->nextId(), 'game_id' => \App\Models\Game::getValueByName('BINGO', 'id'),
            'room_id' => (string) $this->roomR1->id, 'seller_id' => Seller::where('user_id', $this->sellerA->id)->value('id'),
            'commission' => 4.5, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $preview = $this->actingAs($this->sellerA)
            ->getJson('/bingo-card/' . $this->roomR1->getRouteKey() . '/card-price?country_id=' . $this->country->id);

        $preview->assertOk();
        // Tasa PROPIA del comercio (2.5), no la oficial: 10 USD × 2.5.
        $this->assertEqualsWithDelta(25.00, $preview->json('card_price'), 0.001);
        $this->assertEqualsWithDelta(self::COMMERCE_RATE, $preview->json('exchange_rate'), 0.001);
        // Comisión especial de la sala, no la base.
        $this->assertEqualsWithDelta(4.5, $preview->json('seller_commission'), 0.001);

        $this->sale($this->sellerA, $this->roomR1, ['expected_card_price' => $preview->json('card_price')])->assertOk();

        $lot = BingoCardLot::where('seller_user_id', $this->sellerA->id)->firstOrFail();
        $this->assertEqualsWithDelta(50.00, (float) $lot->sale_amount, 0.001);
        // Lo que el vendedor vio como "Mi Comisión" es lo que se registró.
        $this->assertEqualsWithDelta(round(50.00 * 4.5 / 100, 2), (float) $lot->commission_amount, 0.001);
    }

    // ─── Reparto en cascada de la red comercial ──────────────────────────────

    /** @test */
    public function agency_seller_sale_splits_network_commission_in_cascade(): void
    {
        // Comercializadora C: 5% (fixture). Agencia S de C: 4%. Vendedor F de S: 3%.
        $subsidiaryUser = $this->makeUserWithRole('commerce');
        $subsidiaryId = $this->nextId();
        $commerceId = Commerce::where('user_id', $this->commerceC->id)->value('id');
        DB::table('subsidiaries')->insert([
            'id' => $subsidiaryId, 'name' => 'Agencia S', 'commission' => 4.00,
            'commerce_id' => $commerceId, 'user_id' => $subsidiaryUser->id,
            'country_id' => $this->country->id, 'currency_id' => $this->currency->id,
            'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sellerF = $this->makeSeller($this->commerceC);
        Seller::where('user_id', $sellerF->id)->update(['subsidiary_id' => $subsidiaryId]);

        $this->sale($sellerF, $this->roomR1)->assertOk();

        // Venta 2 × 25 = 50: vendedor 3% = 1.50, agencia 4−3 = 0.50,
        // comercializadora 5−max(3,4) = 0.50.
        $lot = BingoCardLot::where('seller_user_id', $sellerF->id)->firstOrFail();
        $this->assertEqualsWithDelta(1.50, (float) $lot->commission_amount, 0.001);
        $this->assertEqualsWithDelta(0.50, (float) $lot->subsidiary_commission_amount, 0.001);
        $this->assertEqualsWithDelta(0.50, (float) $lot->commerce_commission_amount, 0.001);
        $this->assertSame((string) $subsidiaryId, (string) $lot->subsidiary_id);
        $this->assertSame((string) $commerceId, (string) $lot->commerce_id);

        // Las porciones de red salen del bote (igual que la del vendedor) y
        // quedan registradas con la integridad venta = comisiones + bote.
        $sale = \App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale::where('room_id', $this->roomR1->id)->firstOrFail();
        $this->assertEqualsWithDelta(0.50, (float) $sale->subsidiary_commission, 0.001);
        $this->assertEqualsWithDelta(0.50, (float) $sale->commerce_commission, 0.001);
        $this->assertTrue($sale->is_valid, 'sale_amount debe igualar comisiones + jackpot + bote');

        // El desglose del lote incluye las porciones (se revierten en un reembolso).
        $this->assertEqualsWithDelta(0.50, (float) $lot->financial_breakdown['room_local']['subsidiary_commission'], 0.001);
        $this->assertEqualsWithDelta(0.50, (float) $lot->financial_breakdown['creator_sale']['commerce_commission'], 0.001);
    }

    /** @test */
    public function direct_seller_sale_gives_commerce_the_difference(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();

        // Directo: vendedor 3% = 1.50, comercializadora 5−3 = 1.00, sin agencia.
        $lot = BingoCardLot::where('seller_user_id', $this->sellerA->id)->firstOrFail();
        $this->assertEqualsWithDelta(1.50, (float) $lot->commission_amount, 0.001);
        $this->assertEqualsWithDelta(0.00, (float) $lot->subsidiary_commission_amount, 0.001);
        $this->assertEqualsWithDelta(1.00, (float) $lot->commerce_commission_amount, 0.001);
        $this->assertNull($lot->subsidiary_id);
    }

    // ─── Registro del pago ───────────────────────────────────────────────────

    /** @test */
    public function sale_records_pending_payment_with_private_proof_and_normalized_receipt(): void
    {
        $response = $this->sale($this->sellerA, $this->roomR1, ['receipt_number' => ' ab 123 ']);

        $response->assertOk()->assertJson(['paymentStatus' => 'PENDING']);

        $payment = BingoCardPayment::firstOrFail();
        $this->assertSame('PENDING', $payment->payment_status);
        $this->assertSame((string) $this->sellerA->id, (string) $payment->seller_user_id);
        $this->assertSame('AB123', $payment->transaction_reference);
        $this->assertEqualsWithDelta(50.00, (float) $payment->payment_amount, 0.001);

        // Comprobante en disco privado, nombre no adivinable.
        $this->assertStringStartsWith(BingoCardPayment::PROOF_DIRECTORY . '/', $payment->payment_proof);
        Storage::disk(BingoCardPayment::PROOF_DISK)->assertExists($payment->payment_proof);
        $this->assertStringNotContainsString('receipt_', $payment->payment_proof);
    }

    /** @test */
    public function sale_is_rejected_when_price_changed_since_preview(): void
    {
        // El vendedor vio el precio con la tasa oficial (10) — el vigente es 25.
        $this->sale($this->sellerA, $this->roomR1, ['expected_card_price' => 10.00])
            ->assertStatus(409)
            ->assertJson(['error_code' => 'BINGO_CARD_PRICE_CHANGED']);

        $this->assertSame(0, BingoCardLot::count());
        $this->assertSame(0, BingoCardPayment::count());
    }

    /** @test */
    public function the_same_receipt_cannot_back_two_sales(): void
    {
        $this->sale($this->sellerA, $this->roomR1, ['receipt_number' => 'ab 123'])->assertOk();

        $this->sale($this->sellerA, $this->roomR1, [
            'receipt_number' => 'AB123',
            'player'         => ['phone' => '+584149998877', 'first_name' => 'Luis', 'last_name' => 'Díaz'],
        ])
            ->assertStatus(409)
            ->assertJson(['error_code' => 'BINGO_CARD_DUPLICATE_RECEIPT']);

        $this->assertSame(1, BingoCardLot::count());
    }

    // ─── Sala y moneda ───────────────────────────────────────────────────────

    /** @test */
    public function seller_cannot_sell_in_a_private_room_of_another_commerce(): void
    {
        $this->sale($this->sellerA, $this->privateRoomD)
            ->assertStatus(404)
            ->assertJson(['error_code' => 'BINGO_CARD_ROOM_NOT_AVAILABLE']);

        $this->assertSame(0, BingoCardLot::count());
    }

    /** @test */
    public function seller_cannot_sell_in_a_currency_not_enabled_for_the_room(): void
    {
        $otherCountry = Country::firstOrCreate(
            ['iso_code_2' => 'TX'],
            ['name' => 'Other Test Country', 'iso_code_3' => 'TXC', 'status' => 'ACTIVE']
        );
        $otherCurrency = Currency::firstOrCreate(
            ['iso_code' => 'TXD'],
            ['symbol' => 'X$', 'name' => 'Other Test Dollar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE']
        );
        DB::table('currencies')->where('id', $otherCurrency->id)->update(['country_id' => $otherCountry->id]);

        $this->sale($this->sellerA, $this->roomR1, ['country_id' => (string) $otherCountry->id])
            ->assertStatus(422)
            ->assertJson(['error_code' => 'BINGO_CARD_CURRENCY_NOT_AVAILABLE']);

        $this->assertSame(0, BingoCardLot::count());
    }

    // ─── Revisión del pago ───────────────────────────────────────────────────

    /** @test */
    public function only_the_network_commerce_or_admin_can_review_the_payment(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $payment = BingoCardPayment::firstOrFail();

        $approveUrl = route('bingo-card-payments.approve', ['payment' => $payment]);
        $proofUrl   = route('bingo-card-payments.proof', ['payment' => $payment]);

        // El vendedor no puede aprobar su propia venta (ni ver la cola).
        $this->actingAs($this->sellerA)->postJson($approveUrl)->assertForbidden();

        // Otro comercio tampoco: ni aprobar ni ver el comprobante.
        $this->actingAs($this->commerceD)->postJson($approveUrl)->assertForbidden();
        $this->actingAs($this->commerceD)->get($proofUrl)->assertForbidden();

        // El comercio de la red ve el comprobante y aprueba.
        $this->actingAs($this->commerceC)->get($proofUrl)->assertOk();
        $this->actingAs($this->commerceC)->postJson($approveUrl)->assertOk();

        $this->assertSame('APPROVED', $payment->fresh()->payment_status);
    }

    /** @test */
    public function review_queue_only_lists_payments_of_the_commerce_network(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();

        $this->actingAs($this->commerceD)
            ->get('/bingo/card-payments', ['X-Inertia' => 'true', 'X-Inertia-Version' => \Inertia\Inertia::getVersion()])
            ->assertOk()
            ->assertJsonCount(0, 'props.payments');

        $this->actingAs($this->commerceC)
            ->get('/bingo/card-payments', ['X-Inertia' => 'true', 'X-Inertia-Version' => \Inertia\Inertia::getVersion()])
            ->assertOk()
            ->assertJsonCount(1, 'props.payments');
    }

    // ─── Punto de venta (/bingo-card/pos/*) ──────────────────────────────────

    /** @test */
    public function pos_rooms_lists_own_and_public_rooms_in_one_call_but_not_private_rooms_of_others(): void
    {
        $response = $this->actingAs($this->sellerE)
            ->getJson('/bingo-card/pos/rooms?date=' . now()->addDay()->toDateString())
            ->assertOk();

        $ids = collect($response->json('rooms'))->pluck('id');

        // Propia (privada de D) + pública de C, ambas con sus tipos para filtrar en el cliente.
        $this->assertTrue($ids->contains($this->privateRoomD->getRouteKey()));
        $this->assertTrue($ids->contains($this->roomR1->getRouteKey()));

        $own = collect($response->json('rooms'))->firstWhere('id', $this->privateRoomD->getRouteKey());
        $this->assertTrue($own['is_own']);
        $this->assertNotNull($own['room_type_id']);
        $this->assertEqualsWithDelta(self::ROOM_PRICE_USD, $own['card_price_usd'], 0.001);

        // La sala privada de D nunca le aparece a un vendedor de C.
        $idsA = collect($this->actingAs($this->sellerA)
            ->getJson('/bingo-card/pos/rooms?date=' . now()->addDay()->toDateString())
            ->assertOk()
            ->json('rooms'))->pluck('id');

        $this->assertFalse($idsA->contains($this->privateRoomD->getRouteKey()));
    }

    /** @test */
    public function pos_endpoints_are_only_for_sellers(): void
    {
        foreach (['/bingo-card/pos/rooms?date=' . now()->toDateString(), '/bingo-card/pos/frequent-buyers', '/bingo-card/pos/today-sales'] as $url) {
            $this->actingAs($this->commerceC)->getJson($url)->assertForbidden();
        }
    }

    /** @test */
    public function frequent_buyers_and_today_sales_only_show_the_sellers_own_buyers(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();

        // Búsqueda por nombre y por dígitos del teléfono (se guarda formateado).
        foreach (['', 'ana', '1112233'] as $q) {
            $buyers = $this->actingAs($this->sellerA)
                ->getJson('/bingo-card/pos/frequent-buyers?q=' . $q)
                ->assertOk()
                ->json('buyers');

            $this->assertCount(1, $buyers, "búsqueda '{$q}'");
            $this->assertSame('Ana', $buyers[0]['first_name']);
            $this->assertSame(1, $buyers[0]['purchases']);
        }

        $this->actingAs($this->sellerA)->getJson('/bingo-card/pos/frequent-buyers?q=zzz')->assertOk()->assertJsonCount(0, 'buyers');

        $today = $this->actingAs($this->sellerA)->getJson('/bingo-card/pos/today-sales')->assertOk();
        $this->assertSame(1, $today->json('summary.sales_count'));
        $this->assertSame(2, $today->json('summary.cards_count'));
        $this->assertSame(1, $today->json('summary.pending'));
        $this->assertEqualsWithDelta(50.00, $today->json('summary.totals.0.amount'), 0.001);
        $this->assertSame('PENDING', $today->json('sales.0.status'));
        $this->assertSame('Ana Pérez', $today->json('sales.0.buyer_name'));
        $this->assertNotNull($today->json('sales.0.player_link'));

        // Otro vendedor no ve compradores ni ventas ajenas.
        $this->actingAs($this->sellerE)->getJson('/bingo-card/pos/frequent-buyers')->assertOk()->assertJsonCount(0, 'buyers');
        $this->actingAs($this->sellerE)->getJson('/bingo-card/pos/today-sales')->assertOk()->assertJsonCount(0, 'sales');
    }
}
