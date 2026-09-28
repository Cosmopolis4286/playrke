<?php

namespace Tests\Unit\Services;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Games\Common\ConnectionType\ConnectionType;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Room\RoomType;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\Role;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use App\Services\Roles\Commerce\Report\CommerceEconomySummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cubre el bug de scope que CommerceDashboardContextService también tenía
 * antes de Commerce::networkUserIds(): un commerce con vendedores directos
 * o vendedores de sus agencias (subsidiaries) no veía esas ventas en ningún
 * reporte consolidado propio, solo lo que él vendía con su propia cuenta.
 */
class CommerceEconomySummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;
    private Currency $usd;
    private CommerceEconomySummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CommerceEconomySummaryService::class);

        foreach (['commerce', 'subsidiary', 'seller'] as $name) {
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                Role::create(['name' => $name, 'guard_name' => 'web', 'prefix' => strtoupper($name), 'type' => 'web']);
            }
        }

        $this->country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO'],
        );

        $this->usd = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$', 'type' => 'FIAT']);
    }

    private function makeCommerceUser(): User
    {
        $user = User::factory()->create(['country_id' => $this->country->id]);
        $user->assignRole('commerce');

        Commerce::create([
            'name'           => 'Comercio de prueba',
            'country_id'     => $this->country->id,
            'phone'          => '+58414' . random_int(1000000, 9999999),
            'user_id'        => $user->id,
            'unique_code_id' => UniqueCode::create([
                'rke_code'  => 'RKE-' . random_int(100000, 999999),
                'uuid_code' => (string) Str::uuid(),
                'user_id'   => $user->id,
                'status'    => 'ACTIVE',
            ])->id,
            'commission' => 5.00,
            'status'     => 'ACTIVE',
        ]);

        return $user;
    }

    private function makeSubsidiaryUnderCommerce(Commerce $commerce): User
    {
        $user = User::factory()->create(['country_id' => $this->country->id]);
        $user->assignRole('subsidiary');

        Subsidiary::create([
            'name'        => 'Agencia de prueba',
            'commerce_id' => $commerce->id,
            'user_id'     => $user->id,
            'country_id'  => $this->country->id,
            'currency_id' => $this->usd->id,
            'commission'  => 3.00,
            'status'      => 'ACTIVE',
        ]);

        return $user;
    }

    /** Vendedor DIRECTO del commerce (manager_user_id = commerce). */
    private function makeDirectSeller(User $commerceUser): User
    {
        $user = User::factory()->create(['country_id' => $this->country->id]);
        $user->assignRole('seller');

        Seller::create([
            'id'              => (string) random_int(400000000000000000, 499999999999999999),
            'name'            => 'Vendedor directo',
            'phone'           => '+58424' . random_int(1000000, 9999999),
            'commission'      => 2.00,
            'user_id'         => $user->id,
            'country_id'      => $this->country->id,
            'manager_user_id' => $commerceUser->id,
            'status'          => 'ACTIVE',
        ]);

        return $user;
    }

    /** Vendedor que reporta a través de una Subsidiary (agencia). */
    private function makeSubsidiarySeller(Subsidiary $subsidiary): User
    {
        $user = User::factory()->create(['country_id' => $this->country->id]);
        $user->assignRole('seller');

        Seller::create([
            'id'              => (string) random_int(500000000000000000, 599999999999999999),
            'name'            => 'Vendedor de agencia',
            'phone'           => '+58424' . random_int(1000000, 9999999),
            'commission'      => 2.00,
            'user_id'         => $user->id,
            'country_id'      => $this->country->id,
            'subsidiary_id'   => $subsidiary->id,
            // Mismo dato que persiste ManagerSellerService: el user_id del
            // Commerce raíz, no el de la agencia.
            'manager_user_id' => $subsidiary->commerce->user_id,
            'status'          => 'ACTIVE',
        ]);

        return $user;
    }

    /** Crea sala + BingoCreatorSale (agregado, lo que consume by_currency/totals). */
    private function makeCreatorSale(User $creatorUser, float $saleAmount, float $rkeCommission): void
    {
        $status = GameStatus::firstOrCreate(['code' => 'ACTIVE'], ['label' => 'Activa']);
        $roomType = RoomType::firstOrCreate(['code' => 'PUBLIC'], ['label' => 'Pública']);
        $connectionType = ConnectionType::firstOrCreate(['code' => 'ONLINE'], ['label' => 'Online']);
        $game = Game::firstOrCreate(['name' => 'BINGO'], ['status' => 'ACTIVE']);
        $prizePoolType = PrizePoolType::firstOrCreate(
            ['game_id' => $game->id, 'code' => 'STANDARD'],
            ['label' => 'Estándar', 'is_accumulative' => false],
        );

        $room = BingoRoom::create([
            'uuid_code'       => (string) Str::uuid(),
            'status_id'       => $status->id,
            'creator_user_id' => $creatorUser->id,
        ]);

        BingoRoomConfig::create([
            'room_id'            => $room->id,
            'room_type_id'       => $roomType->id,
            'creator_user_id'    => $creatorUser->id,
            'connection_type_id' => $connectionType->id,
            'prize_pool_type_id' => $prizePoolType->id,
            'game_variant'       => BingoVariant::BINGO_75,
        ]);

        BingoCreatorSale::create([
            'room_id'           => $room->id,
            'creator_user_id'   => $creatorUser->id,
            'currency_id'       => $this->usd->id,
            'sale_amount'       => $saleAmount,
            'rke_commission'    => $rkeCommission,
            'room_commission'   => 0,
            'seller_commission' => 0,
            'prize_pool_amount' => 0,
        ]);
    }

    /** Crea un BingoCardLot (lo que consume by_seller). */
    private function makeCardLot(User $creatorUser, ?User $sellerUser, float $saleAmount, float $commissionAmount): void
    {
        $status = GameStatus::firstOrCreate(['code' => 'ACTIVE'], ['label' => 'Activa']);
        $roomType = RoomType::firstOrCreate(['code' => 'PUBLIC'], ['label' => 'Pública']);
        $connectionType = ConnectionType::firstOrCreate(['code' => 'ONLINE'], ['label' => 'Online']);
        $game = Game::firstOrCreate(['name' => 'BINGO'], ['status' => 'ACTIVE']);
        $prizePoolType = PrizePoolType::firstOrCreate(
            ['game_id' => $game->id, 'code' => 'STANDARD'],
            ['label' => 'Estándar', 'is_accumulative' => false],
        );

        $room = BingoRoom::create([
            'uuid_code'       => (string) Str::uuid(),
            'status_id'       => $status->id,
            'creator_user_id' => $creatorUser->id,
        ]);

        BingoRoomConfig::create([
            'room_id'            => $room->id,
            'room_type_id'       => $roomType->id,
            'creator_user_id'    => $creatorUser->id,
            'connection_type_id' => $connectionType->id,
            'prize_pool_type_id' => $prizePoolType->id,
            'game_variant'       => BingoVariant::BINGO_75,
        ]);

        $roomUser = BingoRoomUser::create([
            'room_id'         => $room->id,
            'user_id'         => $creatorUser->id,
            'cards_purchased' => 1,
        ]);

        BingoCardLot::create([
            'room_id'           => $room->id,
            'user_id'           => $creatorUser->id,
            'seller_user_id'    => $sellerUser?->id,
            'room_user_id'      => $roomUser->id,
            'currency_id'       => $this->usd->id,
            'card_count'        => 1,
            'card_price'        => $saleAmount,
            'exchange_rate'     => 1,
            'sale_amount'       => $saleAmount,
            'commission_amount' => $commissionAmount,
        ]);
    }

    public function test_summary_includes_sales_from_direct_seller_of_the_network(): void
    {
        $commerceUser = $this->makeCommerceUser();
        $commerce = Commerce::where('user_id', $commerceUser->id)->first();
        $sellerUser = $this->makeDirectSeller($commerceUser);

        // La venta la registra el vendedor directo (creator_user_id = vendedor,
        // no el commerce) — antes esto NO se contaba en ningún reporte del commerce.
        $this->makeCreatorSale($sellerUser, 100.0, 10.0);
        $this->makeCardLot($sellerUser, $sellerUser, 100.0, 10.0);

        $summary = $this->service->getSummary($commerceUser->id);

        $this->assertSame(100.0, $summary['totals']['total_income']);
        $this->assertSame(10.0, $summary['totals']['total_commission']);
        $this->assertNotEmpty($summary['by_seller']);
    }

    public function test_summary_includes_sales_from_seller_reporting_through_a_subsidiary(): void
    {
        $commerceUser = $this->makeCommerceUser();
        $commerce = Commerce::where('user_id', $commerceUser->id)->first();
        $subsidiaryUser = $this->makeSubsidiaryUnderCommerce($commerce);
        $subsidiary = Subsidiary::where('user_id', $subsidiaryUser->id)->first();
        $agencySeller = $this->makeSubsidiarySeller($subsidiary);

        // Venta generada por un vendedor de UNA AGENCIA del commerce — este es
        // exactamente el caso que Commerce::networkUserIds() debe capturar.
        $this->makeCreatorSale($agencySeller, 200.0, 16.0);
        $this->makeCardLot($agencySeller, $agencySeller, 200.0, 16.0);

        $summary = $this->service->getSummary($commerceUser->id);

        $this->assertSame(200.0, $summary['totals']['total_income']);
        $this->assertSame(16.0, $summary['totals']['total_commission']);
    }

    public function test_summary_excludes_sales_from_unrelated_seller(): void
    {
        $commerceUser = $this->makeCommerceUser();
        $otherCommerceUser = $this->makeCommerceUser();
        $unrelatedSeller = $this->makeDirectSeller($otherCommerceUser);

        $this->makeCreatorSale($unrelatedSeller, 500.0, 50.0);

        $summary = $this->service->getSummary($commerceUser->id);

        $this->assertSame(0.0, $summary['totals']['total_income']);
    }

    public function test_by_currency_groups_and_sums_correctly(): void
    {
        $commerceUser = $this->makeCommerceUser();

        $this->makeCreatorSale($commerceUser, 100.0, 10.0);
        $this->makeCreatorSale($commerceUser, 50.0, 5.0);

        $summary = $this->service->getSummary($commerceUser->id);

        $this->assertCount(1, $summary['by_currency']);
        $this->assertSame(150.0, $summary['by_currency'][0]['income']);
        $this->assertSame(15.0, $summary['by_currency'][0]['commission']);
        $this->assertSame('USD', $summary['by_currency'][0]['iso_code']);
    }

    public function test_date_range_filter_excludes_sales_outside_range(): void
    {
        $commerceUser = $this->makeCommerceUser();
        $this->makeCreatorSale($commerceUser, 100.0, 10.0);

        $summary = $this->service->getSummary(
            $commerceUser->id,
            startDate: now()->addDays(1)->format('Y-m-d'),
            endDate: now()->addDays(2)->format('Y-m-d'),
        );

        $this->assertSame(0.0, $summary['totals']['total_income']);
    }
}
