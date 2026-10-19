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
            'admin'    => ['games.manage', 'games.bingo', 'games.bingo.manage', 'games.bingo.payments.review'],
            'commerce' => ['games.bingo', 'games.bingo.manage', 'games.bingo.game_rooms', 'games.bingo.payments.review', 'commissions.view-own', 'exchange.view-network'],
            'seller'   => ['games.bingo', 'games.bingo.game_rooms', 'external-players.view', 'commissions.view-own', 'exchange.view-network'],
            'customer' => ['exchange.view-network'],
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
            'payment_method'      => 'mobile_payment',
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
        $this->assertSame('own', $preview->json('exchange_rate_source'), 'El POS debe indicar que la tasa es la propia del comercio.');
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

    // ─── Método de pago ──────────────────────────────────────────────────────

    /** @test */
    public function cash_sale_needs_no_receipt_and_still_goes_to_review(): void
    {
        $this->sale($this->sellerA, $this->roomR1, [
            'payment_method' => 'cash',
            'receipt_number' => null,
            'receipt_image'  => null,
        ])->assertOk()->assertJson(['paymentStatus' => 'PENDING']);

        $payment = BingoCardPayment::firstOrFail();
        $this->assertSame('cash', $payment->payment_method);
        $this->assertSame('PENDING', $payment->payment_status);
        $this->assertNull($payment->transaction_reference);
        $this->assertSame('', $payment->payment_proof);
    }

    /** @test */
    public function cash_sale_ignores_a_receipt_sent_by_mistake(): void
    {
        // Un número enviado junto con "efectivo" no reserva esa referencia.
        $this->sale($this->sellerA, $this->roomR1, ['payment_method' => 'cash', 'receipt_number' => 'AB123'])->assertOk();

        $this->assertNull(BingoCardPayment::firstOrFail()->transaction_reference);

        // Luego una venta real por pago móvil con esa referencia es válida.
        $this->sale($this->sellerA, $this->roomR1, [
            'receipt_number' => 'AB123',
            'player'         => ['phone' => '+584149998877', 'first_name' => 'Luis', 'last_name' => 'Díaz'],
        ])->assertOk();
    }

    /** @test */
    public function mobile_payment_and_transfer_require_the_receipt(): void
    {
        foreach (['mobile_payment', 'bank_transfer'] as $method) {
            $this->sale($this->sellerA, $this->roomR1, [
                'payment_method' => $method,
                'receipt_number' => null,
                'receipt_image'  => null,
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['receipt_number', 'receipt_image']);
        }

        $this->sale($this->sellerA, $this->roomR1, ['payment_method' => 'crypto'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);

        $this->assertSame(0, BingoCardLot::count());
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

    /** @test */
    public function approving_the_payment_notifies_the_seller(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $payment = BingoCardPayment::firstOrFail();

        $this->actingAs($this->commerceC)
            ->postJson(route('bingo-card-payments.approve', ['payment' => $payment]))
            ->assertOk();

        $notification = DB::table('notifications')
            ->where('user_id', $this->sellerA->id)
            ->where('type', 'BINGO_SALE_PAYMENT_REVIEWED')
            ->first();

        $this->assertNotNull($notification, 'El vendedor debe recibir aviso de la aprobación.');
        $this->assertStringContainsString('aprobó', $notification->message);
        $this->assertStringContainsString('Ana Pérez', $notification->message);
    }

    /** @test */
    public function rejecting_the_payment_notifies_the_seller_to_refund_the_buyer(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $payment = BingoCardPayment::firstOrFail();

        $this->actingAs($this->commerceC)
            ->postJson(route('bingo-card-payments.reject', ['payment' => $payment]), ['admin_notes' => 'Comprobante ilegible'])
            ->assertOk();

        $notification = DB::table('notifications')
            ->where('user_id', $this->sellerA->id)
            ->where('type', 'BINGO_SALE_PAYMENT_REVIEWED')
            ->first();

        $this->assertNotNull($notification, 'El vendedor debe recibir aviso del rechazo.');
        $this->assertStringContainsString('rechazó', $notification->message);
        $this->assertStringContainsString('devuélveselo al comprador', $notification->message);
        $this->assertStringContainsString('Comprobante ilegible', $notification->message);
    }

    /** @test */
    public function external_player_link_status_shows_pending_then_rejected_cards_and_who_sold_them(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $player  = \App\Models\Roles\Player\External\ExternalPlayer::firstOrFail();
        $service = app(\App\Domains\Games\Bingo\Services\Game\BingoGameService::class);

        // Recién vendido: 2 cartones esperando revisión.
        $status = $service->getExternalPlayerPurchaseStatus($player);
        $this->assertSame(2, $status['pending_cards']);
        $this->assertSame(0, $status['rejected_cards']);
        $this->assertSame([], $status['prizes']);
        $this->assertSame('Vendedor ' . $this->sellerA->id, $status['sold_by']);
        $this->assertSame('Comercio ' . $this->commerceC->id, $status['commerce_name']);

        // Rechazado: el comprador ve que se anularon y a quién reclamar.
        $this->actingAs($this->commerceC)
            ->postJson(route('bingo-card-payments.reject', ['payment' => BingoCardPayment::firstOrFail()]))
            ->assertOk();

        $status = $service->getExternalPlayerPurchaseStatus($player->fresh());
        $this->assertSame(0, $status['pending_cards']);
        $this->assertSame(2, $status['rejected_cards']);
    }

    /** @test */
    public function review_queue_menu_item_shows_pending_count_only_to_reviewers(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();

        $reviewItem = function (User $user): ?array {
            $this->actingAs($user);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $bingo = app(\App\Services\Menu\SidebarMenuService::class)->generateMenu()->get('games_bingo');

            return collect($bingo['submenu'] ?? [])->firstWhere('route', 'bingo-card-payments.index');
        };

        // El comercio de la red ve el ítem con 1 pendiente.
        $this->assertSame(1, $reviewItem($this->commerceC)['badge'] ?? null);

        // Otro comercio ve el ítem, pero sin pendientes ajenos.
        $this->assertSame(0, $reviewItem($this->commerceD)['badge'] ?? null);

        // El vendedor no revisa: no ve el ítem.
        $this->assertNull($reviewItem($this->sellerA));
    }

    // ─── Jugadores externos del vendedor (/admin/external-players) ──────────

    /** @test */
    public function seller_sees_only_the_external_players_they_captured(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $this->sale($this->sellerE, $this->roomR1, [
            'player' => ['phone' => '+584147778899', 'first_name' => 'Pedro', 'last_name' => 'Ajeno'],
        ]);

        $names = collect($this->actingAs($this->sellerA)->getJson('/admin/external-players/fetch-list')->assertOk()->json('players.data'))
            ->map(fn ($p) => trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')))
            ->all();

        // Antes la lista del vendedor salía siempre vacía (rol sin alcance propio).
        $this->assertSame(['Ana Pérez'], $names);

        $own   = \App\Models\Roles\Player\External\ExternalPlayer::where('seller_user_id', $this->sellerA->id)->firstOrFail();
        $other = \App\Models\Roles\Player\External\ExternalPlayer::where('seller_user_id', '!=', $this->sellerA->id)->first();

        $this->assertTrue($this->sellerA->can('view', $own));
        if ($other) {
            $this->assertFalse($this->sellerA->can('view', $other));
        }
    }

    // ─── Compra por cuenta propia con código (/comprar/{token}) ──────────────

    private const BUYER_PHONE = '+584141112233';

    private function salesLinkToken(User $seller): string
    {
        return (string) $this->actingAs($seller)->getJson('/bingo-card/pos/sales-link')->assertOk()->json('link.token');
    }

    private function issueCode(User $seller, string $phone = self::BUYER_PHONE, int $uses = 5, int $days = 30): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($seller)->postJson('/bingo-card/pos/purchase-codes', [
            'country_id' => (string) $this->country->id,
            'phone'      => $phone,
            'max_uses'   => $uses,
            'valid_days' => $days,
        ]);
    }

    /** El comprador no tiene cuenta: las requests públicas van sin usuario autenticado. */
    private function asGuest(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function verifyCode(string $token, string $code, string $phone = '+58 414-111-2233'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson("/comprar/{$token}/verify", [
            'country_id' => (string) $this->country->id,
            'phone'      => $phone, // otro formato: se compara por dígitos
            'code'       => strtolower($code),
        ]);
    }

    private function publicPurchase(string $token, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post("/comprar/{$token}/rooms/{$this->roomR1->getRouteKey()}/purchase", array_merge([
            'card_count'          => 2,
            'payment_method'      => 'bank_transfer',
            'receipt_number'      => 'PUB-' . Str::random(6),
            'receipt_image'       => UploadedFile::fake()->image('pago.jpg'),
            'expected_card_price' => self::ROOM_PRICE_USD * self::COMMERCE_RATE,
        ], $overrides), ['Accept' => 'application/json']);
    }

    /** @test */
    public function seller_can_only_give_a_code_to_a_buyer_who_already_bought_from_them(): void
    {
        // Nunca le compró: no se puede otorgar.
        $this->issueCode($this->sellerA)->assertStatus(422);

        $this->sale($this->sellerA, $this->roomR1)->assertOk();

        $first = $this->issueCode($this->sellerA, uses: 5)->assertOk()->json('code');
        $this->assertSame(5, $first['remaining_uses']);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $first['code']);

        // Otro vendedor no puede darle código a un comprador ajeno.
        $this->issueCode($this->sellerE)->assertStatus(422);

        // Un código nuevo reemplaza al vigente anterior.
        $this->issueCode($this->sellerA, uses: 1)->assertOk();
        $statuses = collect($this->actingAs($this->sellerA)->getJson('/bingo-card/pos/purchase-codes')->json('codes'))
            ->pluck('status', 'code');
        $this->assertSame('Revocado', $statuses[$first['code']]);
    }

    /** @test */
    public function recurring_buyer_buys_with_code_and_sale_goes_to_review_under_the_seller(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $code  = $this->issueCode($this->sellerA, uses: 5)->json('code.code');
        $token = $this->salesLinkToken($this->sellerA);

        $this->asGuest();

        $this->verifyCode($token, $code)->assertOk()->assertJson(['buyer_name' => 'Ana Pérez', 'remaining_uses' => 5]);

        $this->getJson("/comprar/{$token}/rooms?date=" . now()->addDay()->toDateString())
            ->assertOk()
            ->assertJsonFragment(['id' => $this->roomR1->getRouteKey()]);

        $this->getJson("/comprar/{$token}/rooms/{$this->roomR1->getRouteKey()}/price")
            ->assertOk()
            ->assertJsonMissingPath('seller_commission')
            ->assertJson(['card_price' => 25]);

        $this->publicPurchase($token)
            ->assertOk()
            ->assertJson(['paymentStatus' => 'PENDING', 'remaining_uses' => 4]);

        // Registrada como venta del vendedor A, en revisión, con su comisión.
        $this->assertSame(2, BingoCardLot::where('seller_user_id', $this->sellerA->id)->count());
        $this->assertSame(2, BingoCardPayment::where('payment_status', 'PENDING')->count());
        $this->assertSame('bank_transfer', BingoCardPayment::latest('id')->first()->payment_method);

        // Mismo comprador (mismo registro y mismo link en la sala), no un duplicado.
        $this->assertSame(1, \App\Models\Roles\Player\External\ExternalPlayer::count());
    }

    /** @test */
    public function code_expires_after_its_uses_run_out(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $code  = $this->issueCode($this->sellerA, uses: 1)->json('code.code');
        $token = $this->salesLinkToken($this->sellerA);

        $this->asGuest();
        $this->verifyCode($token, $code)->assertOk();
        $this->publicPurchase($token)->assertOk()->assertJson(['remaining_uses' => 0]);

        // Agotado: ni la sesión abierta ni un nuevo intento sirven.
        $this->publicPurchase($token)->assertStatus(401);
        $this->verifyCode($token, $code)->assertStatus(422);
    }

    /** @test */
    public function code_expires_after_its_validity_period(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $code  = $this->issueCode($this->sellerA, uses: 10, days: 7)->json('code.code');
        $token = $this->salesLinkToken($this->sellerA);

        $this->travel(8)->days();

        $this->asGuest();
        $this->verifyCode($token, $code)->assertStatus(422);
    }

    /** @test */
    public function a_failed_purchase_gives_the_use_back(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $code  = $this->issueCode($this->sellerA, uses: 1)->json('code.code');
        $token = $this->salesLinkToken($this->sellerA);

        $this->asGuest();
        $this->verifyCode($token, $code)->assertOk();

        // Precio desactualizado: la venta no se registra y el uso se devuelve.
        $this->publicPurchase($token, ['expected_card_price' => 10])
            ->assertStatus(409)
            ->assertJson(['error_code' => 'BINGO_CARD_PRICE_CHANGED']);

        $this->assertSame(0, \App\Models\Roles\Player\External\ExternalBuyerPurchaseCode::firstOrFail()->uses_count);
        $this->publicPurchase($token)->assertOk();
    }

    /** @test */
    public function public_purchase_requires_a_verified_session_and_a_receipt(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $code  = $this->issueCode($this->sellerA)->json('code.code');
        $token = $this->salesLinkToken($this->sellerA);

        $this->asGuest();

        // Sin verificar el código: nada.
        $this->publicPurchase($token)->assertStatus(401);
        $this->getJson("/comprar/{$token}/rooms")->assertStatus(401);

        $this->verifyCode($token, $code)->assertOk();

        // Compra a distancia: siempre con comprobante, nunca efectivo.
        $this->publicPurchase($token, ['payment_method' => 'cash'])->assertStatus(422)->assertJsonValidationErrors('payment_method');
        $this->publicPurchase($token, ['receipt_image' => null])->assertStatus(422)->assertJsonValidationErrors('receipt_image');

        $this->assertSame(1, BingoCardLot::count());
    }

    /** @test */
    public function revoked_code_and_regenerated_link_stop_working(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $issued = $this->issueCode($this->sellerA)->json('code');
        $token  = $this->salesLinkToken($this->sellerA);

        $this->actingAs($this->sellerA)->deleteJson("/bingo-card/pos/purchase-codes/{$issued['id']}")->assertOk();

        $this->asGuest();
        $this->verifyCode($token, $issued['code'])->assertStatus(422);

        // Regenerar el enlace invalida el anterior.
        $newToken = (string) $this->actingAs($this->sellerA)->postJson('/bingo-card/pos/sales-link/regenerate')->json('link.token');
        $this->assertNotSame($token, $newToken);

        $this->asGuest();
        $this->get("/comprar/{$token}")->assertNotFound();
    }

    /** @test */
    public function wrong_codes_are_locked_out_after_too_many_attempts(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $code  = $this->issueCode($this->sellerA)->json('code.code');
        $token = $this->salesLinkToken($this->sellerA);

        $this->asGuest();

        $max = (int) config('sellers.purchase_codes.max_failed_attempts');
        for ($i = 0; $i < $max; $i++) {
            $this->verifyCode($token, 'ZZZZZZZZ')->assertStatus(422);
        }

        // Bloqueado: ni siquiera el código correcto entra hasta que pase el tiempo.
        $this->verifyCode($token, $code)->assertStatus(429);
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

    /**
     * Una sala DEMO (de prueba, sin dinero real) solo la juegan su creador y
     * admin: los vendedores de la comercializadora dueña no deben verla en el
     * punto de venta ni poder venderla, aunque accessibleBy() la considere
     * "propia".
     *
     * @test
     */
    public function sellers_cannot_see_or_sell_the_commerce_demo_rooms(): void
    {
        $demoRoom = $this->makeRoom($this->commerceD, 'DEMO');

        $ids = collect($this->actingAs($this->sellerE)
            ->getJson('/bingo-card/pos/rooms?date=' . now()->addDay()->toDateString())
            ->assertOk()
            ->json('rooms'))->pluck('id');

        $this->assertFalse($ids->contains($demoRoom->getRouteKey()));
        $this->assertTrue($ids->contains($this->privateRoomD->getRouteKey()), 'La privada propia sigue siendo vendible');

        $this->sale($this->sellerE, $demoRoom)->assertNotFound();
        $this->assertSame(0, BingoCardLot::where('room_id', $demoRoom->id)->count());
    }

    // ─── Salas VIP: lista de invitados ───────────────────────────────────────

    /** Jugador registrado (crea el rol si el fixture no lo trae). */
    private function makePlayer(): User
    {
        if (!Role::where('name', 'player')->where('guard_name', 'web')->exists()) {
            (new Role())->forceFill(['name' => 'player', 'guard_name' => 'web', 'prefix' => 'player'])->save();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $this->makeUserWithRole('player');
    }

    /** @test */
    public function vip_guest_list_is_managed_only_by_the_room_creator(): void
    {
        $vip    = $this->makeRoom($this->commerceD, 'VIP');
        $player = $this->makePlayer();
        $url    = '/bingo-rooms/' . $vip->getRouteKey() . '/invitations';

        $this->actingAs($this->commerceD)
            ->postJson($url, ['type' => 'player', 'identifier' => $player->email])
            ->assertOk();
        $list = $this->actingAs($this->commerceD)
            ->postJson($url, ['type' => 'phone', 'phone' => '+58 0414-1112233', 'label' => 'Ana'])
            ->assertOk()
            ->json('invitations');
        $this->assertCount(2, $list);

        // Mismo teléfono con otro formato: ya está en la lista.
        $this->actingAs($this->commerceD)
            ->postJson($url, ['type' => 'phone', 'phone' => '+584141112233'])
            ->assertStatus(409);

        // Otra comercializadora no puede ver ni tocar la lista.
        $this->actingAs($this->commerceC)->getJson($url)->assertForbidden();

        // La lista solo aplica a salas VIP.
        $this->actingAs($this->commerceD)
            ->postJson('/bingo-rooms/' . $this->privateRoomD->getRouteKey() . '/invitations', ['type' => 'phone', 'phone' => '+584141112233'])
            ->assertStatus(422);

        // Quitar un invitado.
        $phoneInvite = collect($list)->firstWhere('type', 'phone');
        $this->actingAs($this->commerceD)
            ->deleteJson($url . '/' . $phoneInvite['id'])
            ->assertOk()
            ->assertJsonCount(1, 'invitations');
    }

    /** @test */
    public function sellers_only_sell_vip_rooms_to_invited_phones(): void
    {
        $vip = $this->makeRoom($this->commerceD, 'VIP');

        // El comprador (+584141112233, ver sale()) no está invitado.
        $this->sale($this->sellerE, $vip)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'BINGO_CARD_VIP_BUYER_NOT_INVITED');
        $this->assertSame(0, BingoCardLot::where('room_id', $vip->id)->count());

        // Invitado con el teléfono en otro formato: la venta pasa.
        app(\App\Domains\Games\Bingo\Services\Room\BingoRoomInvitationService::class)
            ->addPhone($vip, '+58 0414-1112233', 'Ana', (string) $this->commerceD->id);

        $this->sale($this->sellerE, $vip)->assertOk();
        $this->assertSame(1, BingoCardLot::where('room_id', $vip->id)->count());
    }

    /** @test */
    public function only_invited_players_see_and_enter_a_vip_room(): void
    {
        $vip      = $this->makeRoom($this->commerceD, 'VIP');
        $invited  = $this->makePlayer();
        $stranger = $this->makePlayer();
        app(\App\Domains\Games\Bingo\Services\Room\BingoRoomInvitationService::class)
            ->addPlayer($vip, $invited->email, (string) $this->commerceD->id);

        $crud = app(\App\Domains\Games\Bingo\Services\Room\BingoRoomCrudService::class);

        $this->actingAs($invited);
        $this->assertTrue($crud->canPlayerAccessRoom($vip->fresh()));

        $this->actingAs($stranger);
        $this->assertFalse($crud->canPlayerAccessRoom($vip->fresh()));

        // Lobby: la VIP le aparece solo al invitado (junto con las públicas).
        $repo  = app(\App\Domains\Games\Bingo\Contracts\Room\BingoRoomRepositoryInterface::class);
        $date  = now()->addDay()->toDateString();
        $seen  = fn (User $u) => $repo->getActiveRoomsForViewWithRoleFilter((int) $u->id, $date, true)->pluck('id')->map(fn ($id) => (string) $id);

        $this->assertTrue($seen($invited)->contains((string) $vip->id));
        $this->assertFalse($seen($stranger)->contains((string) $vip->id));
    }

    /**
     * Requisitos de acceso automático: un jugador registrado que cumple TODOS
     * los fijados (nivel y gasto de los últimos 30 días) entra a la VIP sin
     * invitación; quien no los cumple, no.
     *
     * @test
     */
    public function vip_requirements_grant_access_without_invitation(): void
    {
        $vip    = $this->makeRoom($this->commerceD, 'VIP');
        $url    = '/bingo-rooms/' . $vip->getRouteKey() . '/invitations/requirements';

        $this->actingAs($this->commerceD)
            ->patchJson($url, ['min_level' => 3, 'min_spend_usd' => 20])
            ->assertOk()
            ->assertJsonPath('requirements.min_level', 3);

        $qualified = $this->makePlayer();
        $lowLevel  = $this->makePlayer();
        $noSpend   = $this->makePlayer();

        foreach ([[$qualified, 5], [$lowLevel, 2], [$noSpend, 5]] as [$player, $level]) {
            \App\Models\UserLevel::create([
                'user_id' => $player->id, 'current_level' => $level, 'current_xp' => 0,
                'total_xp' => 0, 'xp_to_next_level' => 1000,
            ]);
        }

        // Gasto propio (compra con billetera: user_id = jugador, sin vendedor).
        foreach ([$qualified, $lowLevel] as $player) {
            BingoCardLot::create([
                'room_id' => $this->roomR1->id, 'user_id' => $player->id, 'seller_user_id' => null,
                'seller_type' => 'USER', 'room_user_id' => '1', 'currency_id' => $this->currency->id,
                'card_count' => 3, 'card_price' => 10, 'exchange_rate' => 1, 'sale_amount' => 30,
                'commission_amount' => 0, 'status' => 'ACTIVE',
            ]);
        }

        $crud = app(\App\Domains\Games\Bingo\Services\Room\BingoRoomCrudService::class);
        $repo = app(\App\Domains\Games\Bingo\Contracts\Room\BingoRoomRepositoryInterface::class);
        $date = now()->addDay()->toDateString();

        foreach ([[$qualified, true], [$lowLevel, false], [$noSpend, false]] as [$player, $expected]) {
            $this->actingAs($player);
            $this->assertSame($expected, $crud->canPlayerAccessRoom($vip->fresh()));
            $this->assertSame(
                $expected,
                $repo->getActiveRoomsForViewWithRoleFilter((int) $player->id, $date, true)->pluck('id')->map(fn ($id) => (string) $id)->contains((string) $vip->id)
            );
        }

        // Quitar los requisitos: vuelve a ser solo por invitación.
        $this->actingAs($this->commerceD)->patchJson($url, ['min_level' => null, 'min_spend_usd' => null])->assertOk();
        $this->actingAs($qualified);
        $this->assertFalse($crud->canPlayerAccessRoom($vip->fresh()));
    }

    /** @test */
    public function pos_marks_vip_rooms(): void
    {
        $vip = $this->makeRoom($this->commerceD, 'VIP');

        $rooms = collect($this->actingAs($this->sellerE)
            ->getJson('/bingo-card/pos/rooms?date=' . now()->addDay()->toDateString())
            ->assertOk()
            ->json('rooms'))->keyBy('id');

        $this->assertTrue($rooms[$vip->getRouteKey()]['is_vip']);
        $this->assertFalse($rooms[$this->privateRoomD->getRouteKey()]['is_vip']);
    }

    /**
     * Venta cruzada: con allow_network_sales = false, una sala PÚBLICA solo la
     * vende la red de su creador. Por defecto (true) cualquier red la vende.
     *
     * @test
     */
    public function public_room_without_network_sales_is_sold_only_by_its_own_network(): void
    {
        $date = now()->addDay()->toDateString();
        $posIds = fn (User $seller) => collect($this->actingAs($seller)
            ->getJson('/bingo-card/pos/rooms?date=' . $date)
            ->assertOk()
            ->json('rooms'))->pluck('id');

        // Por defecto: otra red (E, de D) ve y vende la pública de C.
        $this->assertTrue($posIds($this->sellerE)->contains($this->roomR1->getRouteKey()));

        DB::table('bingo_rooms_config')->where('room_id', $this->roomR1->id)->update(['allow_network_sales' => false]);

        $this->assertFalse($posIds($this->sellerE)->contains($this->roomR1->getRouteKey()));
        $this->sale($this->sellerE, $this->roomR1)->assertNotFound();

        // La red del creador (A, de C) la sigue vendiendo.
        $this->assertTrue($posIds($this->sellerA)->contains($this->roomR1->getRouteKey()));
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
    }

    // ─── Mis comisiones ──────────────────────────────────────────────────────

    /**
     * Cada uno ve solo sus comisiones: el vendedor su venta (pendiente
     * mientras el pago está en revisión, cobrada en efectivo), y la
     * comercializadora su comisión de red; otro vendedor no ve nada.
     *
     * @test
     */
    public function commission_ledger_shows_only_own_commissions(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();

        $sellerRows = collect($this->actingAs($this->sellerA)->getJson('/commissions/data')->assertOk()->json('data.rows'));
        $this->assertCount(1, $sellerRows);
        $this->assertSame('seller', $sellerRows[0]['source']);
        $this->assertSame('cash', $sellerRows[0]['how']);
        $this->assertSame('pending', $sellerRows[0]['status'], 'Pago en revisión');
        $this->assertGreaterThan(0, $sellerRows[0]['commission']);

        // Comercializadora C: su comisión de red (5% de C − 3% del vendedor).
        $commerceRows = collect($this->actingAs($this->commerceC)->getJson('/commissions/data')->assertOk()->json('data.rows'));
        $this->assertTrue($commerceRows->contains('source', 'network'));
        $this->assertFalse($commerceRows->contains('source', 'seller'), 'No ve la comisión del vendedor como suya');

        // Otro vendedor no ve la venta ajena.
        $this->assertSame([], $this->actingAs($this->sellerE)->getJson('/commissions/data')->assertOk()->json('data.rows'));

        // Filtro por estado.
        $this->assertSame([], $this->actingAs($this->sellerA)->getJson('/commissions/data?status=paid')->assertOk()->json('data.rows'));
    }

    /** @test */
    public function commission_ledger_is_only_for_business_roles(): void
    {
        // Admin no tiene el permiso ni comisiones propias.
        $this->actingAs($this->admin)->getJson('/commissions/data')->assertForbidden();
        $this->actingAs($this->sellerA)->get('/commissions/export')->assertOk();
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
        $this->assertEqualsWithDelta(self::COMMERCE_RATE, $today->json('sales.0.exchange_rate'), 0.001);
        $this->assertNotNull($today->json('sales.0.player_link'));

        // Otro vendedor no ve compradores ni ventas ajenas.
        $this->actingAs($this->sellerE)->getJson('/bingo-card/pos/frequent-buyers')->assertOk()->assertJsonCount(0, 'buyers');
        $this->actingAs($this->sellerE)->getJson('/bingo-card/pos/today-sales')->assertOk()->assertJsonCount(0, 'sales');
    }

    // ─── Plazo de venta del vendedor ─────────────────────────────────────────
    // Su pago nace PENDING y los cartones no juegan hasta la aprobación del
    // comercio: la venta cierra N minutos antes del inicio y nunca en IN_PROGRESS.

    private function startRoomIn(BingoRoom $room, int $minutes): void
    {
        $start = now()->addMinutes($minutes);
        DB::table('bingo_rooms_config')->where('room_id', $room->id)->update([
            'date' => $start->toDateString(), 'time' => $start->format('H:i:s'), 'unix_date' => $start->timestamp,
        ]);
    }

    private function posRoom(User $seller, BingoRoom $room, string $date): ?array
    {
        return collect($this->actingAs($seller)->getJson('/bingo-card/pos/rooms?date=' . $date)->assertOk()->json('rooms'))
            ->firstWhere('id', $room->getRouteKey());
    }

    /** @test */
    public function seller_sales_close_minutes_before_the_start(): void
    {
        config(['app.bingo_seller_sales_cutoff_minutes' => 5]);

        // Empieza en 3 min: ya dentro del cierre de 5.
        $this->startRoomIn($this->roomR1, 3);

        $this->sale($this->sellerA, $this->roomR1)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'BINGO_CARD_SALES_CLOSED');

        $this->actingAs($this->sellerA)
            ->getJson('/bingo-card/' . $this->roomR1->getRouteKey() . '/card-price?country_id=' . $this->country->id)
            ->assertStatus(409);

        // Se lista igual, marcada como cerrada.
        $listed = $this->posRoom($this->sellerA, $this->roomR1, now()->toDateString());
        $this->assertNotNull($listed);
        $this->assertFalse($listed['sales_open']);
        $this->assertNotNull($listed['sales_close_at']);

        // Empieza en 10 min: todavía abierta.
        $this->startRoomIn($this->roomR1, 10);
        $this->assertTrue($this->posRoom($this->sellerA, $this->roomR1, now()->toDateString())['sales_open']);
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
    }

    /** @test */
    public function seller_cannot_sell_once_the_draw_is_in_progress(): void
    {
        DB::table('bingo_rooms')->where('id', $this->roomR1->id)->update(['status_id' => GameStatus::codeToId('IN_PROGRESS')]);

        $this->sale($this->sellerA, $this->roomR1)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'BINGO_CARD_SALES_CLOSED');

        $this->assertSame(0, BingoCardLot::where('seller_user_id', $this->sellerA->id)->count());
    }

    /** @test */
    public function manual_start_rooms_are_listed_today_and_sellable_until_started(): void
    {
        DB::table('bingo_rooms_config')->where('room_id', $this->roomR1->id)->update([
            'date' => null, 'time' => null, 'unix_date' => null, 'start_mode' => 'MANUAL',
        ]);

        $listed = $this->posRoom($this->sellerA, $this->roomR1, now()->toDateString());
        $this->assertNotNull($listed, 'La sala manual debe aparecer en "hoy".');
        $this->assertTrue($listed['is_manual']);
        $this->assertTrue($listed['sales_open']);
        $this->assertNull($listed['sales_close_at']);

        // No aparece en otras fechas.
        $this->assertNull($this->posRoom($this->sellerA, $this->roomR1, now()->addDay()->toDateString()));

        $this->sale($this->sellerA, $this->roomR1)->assertOk();
    }

    // ─── Pagos pendientes al iniciar el sorteo ───────────────────────────────
    // BingoPendingPaymentStartService: aviso previo y, al iniciar, la regla
    // de la comercializadora (rechazar con reembolso o mantener en revisión).

    private function runPendingAtStart(): array
    {
        return app(\App\Domains\Games\Bingo\Services\Payment\BingoPendingPaymentStartService::class)->run();
    }

    private function gameAlertsFor(User $user): int
    {
        return DB::table('notifications')->where('user_id', $user->id)->where('type', 'GAME_ALERT')->count();
    }

    /** @test */
    public function commerce_is_alerted_once_when_a_room_is_about_to_start_with_pending_payments(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $this->startRoomIn($this->roomR1, 3);

        $this->assertSame(1, $this->runPendingAtStart()['alerted']);
        $this->assertNotNull(BingoCardPayment::firstOrFail()->start_alert_sent_at);
        $this->assertSame(1, $this->gameAlertsFor($this->commerceC));

        // Idempotente: no repite el aviso.
        $this->assertSame(0, $this->runPendingAtStart()['alerted']);
        $this->assertSame(1, $this->gameAlertsFor($this->commerceC));
    }

    /** @test */
    public function pending_payment_is_kept_in_review_at_start_by_default_and_late_approval_needs_confirmation(): void
    {
        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $payment = BingoCardPayment::firstOrFail();
        $this->startRoomIn($this->roomR1, -1);

        $result = $this->runPendingAtStart();
        $this->assertSame(1, $result['kept']);
        $this->assertSame(0, $result['rejected']);

        $payment->refresh();
        $this->assertSame('PENDING', $payment->payment_status);
        $this->assertNotNull($payment->pending_at_start_at);
        $this->assertSame(1, $this->gameAlertsFor($this->commerceC));

        $url = route('bingo-card-payments.approve', ['payment' => $payment]);

        $this->actingAs($this->commerceC)->postJson($url)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'BINGO_PAYMENT_LATE_APPROVAL_CONFIRM');
        $this->assertSame('PENDING', $payment->fresh()->payment_status);

        $this->actingAs($this->commerceC)->postJson($url, ['confirm_late' => true])->assertOk();
        $this->assertSame('APPROVED', $payment->fresh()->payment_status);
    }

    /** @test */
    public function pending_payment_is_rejected_with_refund_at_start_when_the_commerce_chose_it(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $this->actingAs($this->commerceC)
            ->patchJson(route('bingo-card-payments.policy'), ['policy' => Commerce::PENDING_POLICY_REJECT])
            ->assertOk()
            ->assertJsonPath('policy', Commerce::PENDING_POLICY_REJECT);

        $this->sale($this->sellerA, $this->roomR1)->assertOk();
        $payment = BingoCardPayment::firstOrFail();
        $this->startRoomIn($this->roomR1, -1);

        $this->assertSame(1, $this->runPendingAtStart()['rejected']);

        $payment->refresh();
        $this->assertSame('REJECTED', $payment->payment_status);
        $this->assertNotNull($payment->pending_at_start_at);
        $this->assertSame(0, \App\Domains\Games\Bingo\Models\Card\BingoCard::where('card_lot_id', $payment->card_lot_id)
            ->where('status', \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE)->count());
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob::class);

        // Ya marcado: una segunda corrida no lo vuelve a tocar.
        $this->assertSame(['alerted' => 0, 'rejected' => 0, 'kept' => 0, 'failed' => 0], $this->runPendingAtStart());
    }

    /** @test */
    public function only_the_commerce_can_change_its_pending_payment_policy(): void
    {
        $this->actingAs($this->commerceC)
            ->patchJson(route('bingo-card-payments.policy'), ['policy' => 'otra'])
            ->assertStatus(422);

        $this->actingAs($this->sellerA)
            ->patchJson(route('bingo-card-payments.policy'), ['policy' => Commerce::PENDING_POLICY_REJECT])
            ->assertForbidden();

        $this->assertSame(Commerce::PENDING_POLICY_KEEP, Commerce::where('user_id', $this->commerceC->id)->value('pending_payment_policy'));
    }

    // ─── Tasa de cambio de la red (widget) ───────────────────────────────────

    private function officialRate(float $amount): void
    {
        Cache::flush();
        DB::table('exchange_rates')->insert([
            'id' => $this->nextId(), 'currency_id' => $this->currency->id, 'user_id' => $this->admin->id,
            'amount' => $amount, 'percentage' => 0, 'status' => 'ACTIVE', 'source' => 'user',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function networkRate(User $user): array
    {
        $response = $this->actingAs($user)->getJson(route('exchange-rates.network'))->assertOk();

        return collect($response->json('data.rates'))->firstWhere('currency_id', (string) $this->currency->id);
    }

    /** @test */
    public function seller_sees_the_commerce_own_rate_it_sells_with_compared_to_the_official(): void
    {
        $this->officialRate(2.0);

        $rate = $this->networkRate($this->sellerA);

        $this->assertSame('own', $rate['source']);
        $this->assertEqualsWithDelta(self::COMMERCE_RATE, $rate['effective'], 0.0001);
        $this->assertEqualsWithDelta(2.0, $rate['official']['amount'], 0.0001);
        $this->assertEqualsWithDelta(25.0, $rate['deviation_pct'], 0.001);

        $this->actingAs($this->sellerA)->getJson(route('exchange-rates.network'))
            ->assertJsonPath('data.owner.type', 'commerce')
            ->assertJsonPath('data.can_manage', false);
    }

    /** @test */
    public function network_without_own_rate_sees_the_official_rate(): void
    {
        $this->officialRate(2.0);

        $rate = $this->networkRate($this->sellerE);

        $this->assertSame('official', $rate['source']);
        $this->assertEqualsWithDelta(2.0, $rate['effective'], 0.0001);
        $this->assertNull($rate['own']);
    }

    /** @test */
    public function network_exchange_rate_requires_its_permission(): void
    {
        // admin no tiene exchange.view-network en este fixture.
        $this->actingAs($this->admin)->getJson(route('exchange-rates.network'))->assertForbidden();
    }

    /** @test */
    public function customer_rooms_use_its_own_active_rate_or_the_official_one(): void
    {
        $this->officialRate(2.0);

        $user = $this->makeUserWithRole('customer');
        $customerId = $this->nextId();
        DB::table('customers')->insert([
            'id' => $customerId, 'name' => 'Cliente API', 'phone' => '+58412' . random_int(1000000, 9999999),
            'api_route' => 'https://api.example.test', 'customer_key' => (string) Str::uuid(),
            'country_id' => $this->country->id, 'currency_id' => $this->currency->id, 'user_id' => $user->id,
            'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $rateId = $this->nextId();
        DB::table('customers_exchange_rates')->insert([
            'id' => $rateId, 'customer_id' => $customerId, 'currency_id' => $this->currency->id,
            'amount' => 3.0, 'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $domain = app(\App\Services\ExchangeRate\ExchangeRateDomainService::class);

        // Tasa propia activa → la usan sus salas.
        $this->assertEqualsWithDelta(3.0, $domain->getExchangeRateByCurrency((int) $this->currency->id, (int) $user->id), 0.0001);
        $this->assertSame('own', $this->networkRate($user)['source']);

        // Desactivada → la oficial.
        DB::table('customers_exchange_rates')->where('id', $rateId)->update(['status' => 'INACTIVE']);
        $this->assertEqualsWithDelta(2.0, $domain->getExchangeRateByCurrency((int) $this->currency->id, (int) $user->id), 0.0001);
        $this->assertSame('official', $this->networkRate($user)['source']);
    }

    // ─── Alertas de tasas (Fase 3) ───────────────────────────────────────────

    private function runRateAlerts(): array
    {
        return app(\App\Services\ExchangeRate\ExchangeRateAlertService::class)->run();
    }

    private function rateAlertsFor(User $user): int
    {
        return DB::table('notifications')->where('user_id', $user->id)->where('type', 'EXCHANGE_RATE')->count();
    }

    /** @test */
    public function official_rate_change_is_notified_to_networks_selling_with_it(): void
    {
        $this->officialRate(2.0);
        $this->runRateAlerts(); // primer valor: se registra sin avisar
        $this->assertSame(0, $this->rateAlertsFor($this->sellerE));

        DB::table('exchange_rates')->where('currency_id', $this->currency->id)->update(['status' => 'OLD']);
        DB::table('exchange_rates')->insert([
            'id' => $this->nextId(), 'currency_id' => $this->currency->id, 'user_id' => $this->admin->id,
            'amount' => 2.2, 'percentage' => 10, 'status' => 'ACTIVE', 'source' => 'user',
            'created_at' => now()->addSecond(), 'updated_at' => now()->addSecond(),
        ]);

        $this->assertSame(1, $this->runRateAlerts()['official_changes']);

        // Red de D (sin tasa propia) vende con la oficial: avisados.
        $this->assertSame(1, $this->rateAlertsFor($this->commerceD));
        $this->assertSame(1, $this->rateAlertsFor($this->sellerE));
        // Red de C usa su tasa propia: el cambio de la oficial no le aplica.
        $this->assertSame(0, $this->rateAlertsFor($this->sellerA));

        // Sin nuevo cambio, no se repite.
        $this->assertSame(0, $this->runRateAlerts()['official_changes']);
    }

    /** @test */
    public function commerce_own_rate_change_is_notified_to_its_network(): void
    {
        $this->runRateAlerts();

        DB::table('commerces_exchange_rates')->where('currency_id', $this->currency->id)->update(['amount' => 2.8, 'updated_at' => now()]);

        $this->assertSame(1, $this->runRateAlerts()['own_changes']);
        $this->assertSame(1, $this->rateAlertsFor($this->sellerA));
        $this->assertSame(0, $this->rateAlertsFor($this->commerceC), 'Quien cambió la tasa no se avisa a sí mismo.');
        $this->assertSame(0, $this->rateAlertsFor($this->sellerE));
    }

    /** @test */
    public function owner_is_alerted_once_when_its_own_rate_is_stale_or_far_from_the_official(): void
    {
        $this->officialRate(2.0); // propia 2.5 → +25 %
        DB::table('commerces_exchange_rates')->where('currency_id', $this->currency->id)->update(['updated_at' => now()->subDays(2)]);

        $first = $this->runRateAlerts();
        $this->assertSame(1, $first['stale']);
        $this->assertSame(1, $first['deviations']);
        $this->assertSame(2, $this->rateAlertsFor($this->commerceC));

        // Dentro de las 24 h no se repiten.
        $second = $this->runRateAlerts();
        $this->assertSame(0, $second['stale']);
        $this->assertSame(0, $second['deviations']);

        $rate = $this->networkRate($this->sellerA);
        $this->assertTrue($rate['stale']);
        $this->assertTrue($rate['deviation_alert']);
    }

    // ─── Tope de desvío de tasas propias ─────────────────────────────────────

    private function setMaxDeviation(string $pct): void
    {
        \App\Models\AppParameter::updateOrCreate(
            ['param' => 'exchange_rate_max_deviation_pct'],
            ['value' => $pct, 'status' => 'ACTIVE'],
        );
        Cache::flush();
    }

    private function commerceOwnRate(): float
    {
        return (float) DB::table('commerces_exchange_rates')
            ->where('commerce_id', Commerce::where('user_id', $this->commerceC->id)->value('id'))
            ->where('status', 'ACTIVE')->orderByDesc('updated_at')->value('amount');
    }

    /** @test */
    public function own_rate_outside_the_admin_deviation_cap_cannot_be_saved(): void
    {
        $this->officialRate(2.0);
        $this->setMaxDeviation('20'); // permitido 1,60 – 2,40

        $crud = app(\App\Services\ExchangeRate\ExchangeRateCrudService::class);
        $dto  = fn (float $amount) => new \App\DTOs\ExchangeRate\ExchangeRateDTO(null, (int) $this->currency->id, $amount, 'ACTIVE');

        try {
            $crud->create($dto(2.6), (int) $this->commerceD->id);
            $this->fail('Una tasa +30 % sobre la oficial debía rechazarse.');
        } catch (\App\Exceptions\Domain\ExchangeRate\ExchangeRateException $e) {
            $this->assertSame('EXCHANGE_RATE_DEVIATION_EXCEEDED', $e->errorCode());
        }

        $this->assertTrue($crud->create($dto(2.3), (int) $this->commerceD->id));

        $rate = $this->networkRate($this->commerceD);
        $this->assertEqualsWithDelta(1.6, $rate['allowed_range']['min'], 0.0001);
        $this->assertEqualsWithDelta(2.4, $rate['allowed_range']['max'], 0.0001);
    }

    /** @test */
    public function automatic_update_skips_own_rates_outside_the_cap(): void
    {
        $this->officialRate(2.0);
        $this->setMaxDeviation('20');

        $updater    = app(\App\Services\ExchangeRate\ExchangeRateUpdaterService::class);
        $commerceId = Commerce::where('user_id', $this->commerceC->id)->value('id');

        $updater->updateForRole('commerce', $commerceId, [$this->currency->iso_code => 3.0]);
        $this->assertEqualsWithDelta(self::COMMERCE_RATE, $this->commerceOwnRate(), 0.0001, 'Fuera del tope: se conserva la anterior.');

        $updater->updateForRole('commerce', $commerceId, [$this->currency->iso_code => 2.2]);
        $this->assertEqualsWithDelta(2.2, $this->commerceOwnRate(), 0.0001);
    }

    /** @test */
    public function without_cap_any_own_rate_is_allowed(): void
    {
        $this->officialRate(2.0);
        $this->setMaxDeviation('0');

        $crud = app(\App\Services\ExchangeRate\ExchangeRateCrudService::class);
        $this->assertTrue($crud->create(
            new \App\DTOs\ExchangeRate\ExchangeRateDTO(null, (int) $this->currency->id, 5.0, 'ACTIVE'),
            (int) $this->commerceD->id,
        ));
        $this->assertNull($this->networkRate($this->commerceD)['allowed_range']);
    }

    // ─── Gestión de salas: detalles y KPIs ───────────────────────────────────

    /** @test */
    public function cancelled_room_details_include_reason_refunds_and_timeline(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->sale($this->sellerA, $this->roomR1)->assertOk();

        if (!GameStatus::where('code', 'CANCELLED')->exists()) {
            GameStatus::create(['code' => 'CANCELLED', 'label' => 'CANCELLED']);
            GameStatus::forgetCache();
        }
        DB::table('bingo_rooms')->where('id', $this->roomR1->id)->update(['status_id' => GameStatus::codeToId('CANCELLED')]);
        app(\App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger::class)->log(
            $this->roomR1->fresh(),
            \App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger::EVENT_CANCELLED,
            'system',
            'No alcanzó el mínimo de cartones',
        );

        $details = $this->actingAs($this->commerceC)
            ->getJson('/bingo-rooms/' . $this->roomR1->getRouteKey() . '/show')
            ->assertOk();

        $this->assertSame($this->roomR1->uuid_code, $details->json('data.uuid_code'));
        $this->assertSame('No alcanzó el mínimo de cartones', $details->json('data.cancellation.reason'));
        $this->assertSame('Sistema', $details->json('data.cancellation.by'));
        // La venta aún no se reembolsó (el job está en cola): queda pendiente.
        $this->assertSame(1, $details->json('data.cancellation.pending_lots'));
        $this->assertSame('Sala cancelada', $details->json('data.timeline.0.label'));
    }

    /** @test */
    public function room_summary_page_includes_status_counts_of_own_rooms(): void
    {
        DB::table('bingo_rooms_config')->where('room_id', $this->roomR1->id)->update(['date' => now()->toDateString()]);

        $this->actingAs($this->commerceC)
            ->get('/bingo-rooms', ['X-Inertia' => 'true', 'X-Inertia-Version' => \Inertia\Inertia::getVersion()])
            ->assertOk()
            ->assertJsonPath('props.statusCounts.total', 1)
            ->assertJsonPath('props.statusCounts.active', 1)
            ->assertJsonPath('props.statusCounts.today', 1);
    }
}
