<?php

namespace Tests\Unit\Services\Platform;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Games\Common\ConnectionType\ConnectionType;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Room\RoomType;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\User;
use App\Services\Platform\PlatformStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlatformStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = app(PlatformStatsService::class);
    }

    private function makeRoom(string $statusCode, int $totalPlayers): BingoRoom
    {
        $country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO'],
        );
        $user = User::factory()->create(['country_id' => $country->id]);

        $status = GameStatus::firstOrCreate(['code' => $statusCode], ['label' => $statusCode]);
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
            'creator_user_id' => $user->id,
            'total_cards'     => 0,
            'total_sales'     => 0,
            'total_players'   => $totalPlayers,
            'prize_pool'      => 0,
            'rke_commission'  => 0,
            'room_commission' => 0,
        ]);

        BingoRoomConfig::create([
            'room_id'            => $room->id,
            'room_type_id'       => $roomType->id,
            'creator_user_id'    => $user->id,
            'connection_type_id' => $connectionType->id,
            'prize_pool_type_id' => $prizePoolType->id,
            'rke_commission'     => 5,
            'room_commission'    => 10,
        ]);

        return $room;
    }

    public function test_players_online_sums_only_active_and_in_progress_rooms(): void
    {
        $this->makeRoom('ACTIVE', 5);
        $this->makeRoom('IN_PROGRESS', 3);
        $this->makeRoom('FINISHED', 100);

        $summary = $this->service->summary();

        $this->assertSame(8, $summary['players_online']);
    }

    public function test_rooms_played_count_counts_only_finished_rooms(): void
    {
        $this->makeRoom('FINISHED', 0);
        $this->makeRoom('FINISHED', 0);
        $this->makeRoom('ACTIVE', 0);

        $summary = $this->service->summary();

        $this->assertSame(2, $summary['rooms_played_count']);
    }

    public function test_total_paid_usd_sums_only_money_win_events(): void
    {
        Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
        $user = User::factory()->create();

        WinEvent::create([
            'user_id' => $user->id, 'game_slug' => 'bingo', 'game_name' => 'Bingo',
            'source_type' => WinEvent::SOURCE_GAME_ENGINE, 'prize_kind' => WinEvent::PRIZE_MONEY,
            'prize_label' => '$10.00', 'amount_usd' => 10.00,
        ]);
        WinEvent::create([
            'user_id' => $user->id, 'game_slug' => 'bingo', 'game_name' => 'Bingo',
            'source_type' => WinEvent::SOURCE_GAME_ENGINE, 'prize_kind' => WinEvent::PRIZE_MONEY,
            'prize_label' => '$5.00', 'amount_usd' => 5.00,
        ]);
        WinEvent::create([
            'user_id' => $user->id, 'game_slug' => 'bingo', 'game_name' => 'Bingo',
            'source_type' => WinEvent::SOURCE_ADMIN_MANUAL, 'prize_kind' => WinEvent::PRIZE_ITEM,
            'prize_label' => 'PlayStation 5',
        ]);

        $summary = $this->service->summary();

        $this->assertEqualsWithDelta(15.00, $summary['total_paid_usd'], 0.001);
    }
}
