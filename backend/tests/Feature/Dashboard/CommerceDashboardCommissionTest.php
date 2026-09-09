<?php

namespace Tests\Feature\Dashboard;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
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
use App\Models\Roles\Commerce;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use App\Services\Roles\Commerce\Dashboard\CommerceDashboardContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * buildStats()['commission'] antes solo mostraba la TASA configurada (%),
 * nunca cuánto dinero representó eso en el mes — este test cubre el
 * cálculo real de commission_amount agrupado por moneda.
 */
class CommerceDashboardCommissionTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;
    private Currency $usd;
    private Currency $eur;
    private User $commerceUser;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Role::where('name', 'commerce')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'commerce', 'guard_name' => 'web', 'prefix' => 'COMMERCE', 'type' => 'web']);
        }

        $this->country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO'],
        );

        $this->usd = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$', 'type' => 'FIAT']);
        $this->eur = Currency::firstOrCreate(['iso_code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'type' => 'FIAT']);

        $this->commerceUser = User::factory()->create(['country_id' => $this->country->id]);
        $this->commerceUser->assignRole('commerce');

        Commerce::create([
            'name'           => 'Comercio de prueba',
            'country_id'     => $this->country->id,
            'phone'          => '+58414' . random_int(1000000, 9999999),
            'user_id'        => $this->commerceUser->id,
            'unique_code_id' => UniqueCode::create([
                'rke_code'  => 'RKE-' . random_int(100000, 999999),
                'uuid_code' => (string) Str::uuid(),
                'user_id'   => $this->commerceUser->id,
                'status'    => 'ACTIVE',
            ])->id,
            'commission' => 8.00,
            'status'     => 'ACTIVE',
        ]);

        Auth::login($this->commerceUser);
    }

    private function makeLot(Currency $currency, float $saleAmount, float $commissionAmount): BingoCardLot
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
            'creator_user_id' => $this->commerceUser->id,
        ]);

        BingoRoomConfig::create([
            'room_id'            => $room->id,
            'room_type_id'       => $roomType->id,
            'creator_user_id'    => $this->commerceUser->id,
            'connection_type_id' => $connectionType->id,
            'prize_pool_type_id' => $prizePoolType->id,
            'game_variant'       => BingoVariant::BINGO_75,
        ]);

        $roomUser = BingoRoomUser::create([
            'room_id'         => $room->id,
            'user_id'         => $this->commerceUser->id,
            'cards_purchased' => 1,
        ]);

        return BingoCardLot::create([
            'room_id'           => $room->id,
            'user_id'           => $this->commerceUser->id,
            'room_user_id'      => $roomUser->id,
            'currency_id'       => $currency->id,
            'card_count'        => 1,
            'card_price'        => $saleAmount,
            'exchange_rate'     => 1,
            'sale_amount'       => $saleAmount,
            'commission_amount' => $commissionAmount,
        ]);
    }

    public function test_commission_stat_shows_real_amount_for_single_currency(): void
    {
        $this->makeLot($this->usd, 100.0, 8.0);
        $this->makeLot($this->usd, 50.0, 4.0);

        $view = app(CommerceDashboardContextService::class)->dashboardContext()->toView();

        $this->assertSame('$ 12.00', $view['stats']['commission']['value']);
        $this->assertStringContainsString('Tasa 8.00%', $view['stats']['commission']['description']);
    }

    public function test_commission_stat_shows_primary_currency_and_counts_the_rest(): void
    {
        $this->makeLot($this->usd, 100.0, 20.0);
        $this->makeLot($this->eur, 50.0, 3.0);

        $view = app(CommerceDashboardContextService::class)->dashboardContext()->toView();

        // USD tiene la mayor comisión (20.0 > 3.0) — es la moneda primaria mostrada.
        $this->assertSame('$ 20.00', $view['stats']['commission']['value']);
        $this->assertStringContainsString('+1 moneda(s) más', $view['stats']['commission']['description']);
    }

    public function test_commission_stat_shows_dash_when_no_sales_this_month(): void
    {
        $view = app(CommerceDashboardContextService::class)->dashboardContext()->toView();

        $this->assertSame('—', $view['stats']['commission']['value']);
        $this->assertSame('Sin comisión este mes', $view['stats']['commission']['description']);
    }

    public function test_sales_by_seller_includes_total_commission(): void
    {
        $this->makeLot($this->usd, 100.0, 8.0);

        $view = app(CommerceDashboardContextService::class)->dashboardContext()->toView();

        $this->assertNotEmpty($view['salesBySeller']);
        $this->assertSame(8.0, $view['salesBySeller'][0]['total_commission']);
    }
}
