<?php

namespace Tests\Feature\Console;

use App\Domains\Games\Bingo\Models\Card\CardPrice\BingoCardPrice;
use App\Domains\Games\Bingo\Models\Creator\BingoCreator;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\Models\Round\BingoRoundPrize;
use App\Models\Games\Common\Utils\GameStatus;
use App\Services\Games\Bingo\Room\Automatic\AutoGenerateBingoRoomsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateBingoRoomsCommandTest extends TestCase
{
    use DatabaseTransactions;

    // ─── Setup ────────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->seedGameStatuses();
    }

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    private function seedGameStatuses(): void
    {
        $statuses = ['ACTIVE', 'INACTIVE', 'DELETED', 'CLOSED', 'IN_PROGRESS', 'FINISHED', 'REFUND', 'OFFLINE'];
        foreach ($statuses as $code) {
            if (!GameStatus::where('code', $code)->exists()) {
                GameStatus::create(['code' => $code, 'label' => $code]);
            }
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static int $idCounter = 0;

    private function nextId(): string
    {
        // Generate unique numeric IDs that won't overflow MySQL BIGINT
        return (string) (100000000000000000 + (++self::$idCounter));
    }

    /**
     * Creates a BingoRoomAutomatic template directly via DB (bypasses SnowflakeId boot).
     */
    private function makeTemplate(array $overrides = []): BingoRoomAutomatic
    {
        $now = Carbon::now('America/Caracas');

        $defaults = [
            'id'                      => $this->nextId(),
            'status'                  => BingoRoomAutomatic::STATUS_ACTIVE,
            'days'                    => json_encode([strtolower($now->englishDayOfWeek)]),
            'time'                    => $now->format('H:i:s'),
            'room_type_id'            => $this->nextId(),
            'game_mode_id'            => $this->nextId(),
            'prize_pool_type_id'      => $this->nextId(),
            'creator_user_id'         => $this->nextId(),
            'rke_commission'          => 5.00,
            'room_commission'         => 10.00,
            'allowed_currencies'      => json_encode(['USD']),
            'use_base_usd'            => 0,
            'card_price_usd'          => null,
            'usd_exchange_round_step' => null,
            'amount_added_prize_pool' => null,
            'time_chip'               => '60',
            'rounds'                  => 1,
            'rounds_pct'              => 100.00,
            'use_serials'             => 0,
            'serials_pct'             => null,
            'num_serials'             => null,
            'jackpot_pct'             => null,
            'in_live'                 => 'N',
            'created_at'              => now(),
            'updated_at'              => now(),
        ];

        // Merge overrides, JSON-encoding array values as needed
        foreach ($overrides as $key => $value) {
            if (is_array($value)) {
                $defaults[$key] = json_encode($value);
            } elseif (is_bool($value)) {
                $defaults[$key] = $value ? 1 : 0;
            } else {
                $defaults[$key] = $value;
            }
        }

        DB::table('bingo_rooms_automatic')->insert($defaults);

        return BingoRoomAutomatic::find($defaults['id']);
    }

    private function addCardPrices(BingoRoomAutomatic $template, array $cardPrices): void
    {
        DB::table('bingo_card_price_automatic')->insert([
            'id'                      => $this->nextId(),
            'automatic_bingo_room_id' => $template->id,
            'creator_user_id'         => $template->creator_user_id,
            'currencies'              => json_encode(array_keys($cardPrices)),
            'card_prices'             => json_encode($cardPrices),
            'status'                  => 'ACTIVE',
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    private function addRoundConfig(
        BingoRoomAutomatic $template,
        array $prizeTypes,
        array $bingoPatterns,
        array $prizeValues
    ): void {
        DB::table('bingo_rounds_automatic')->insert([
            'id'                      => $this->nextId(),
            'automatic_bingo_room_id' => $template->id,
            'rounds'                  => count($prizeTypes),
            'prize_types'             => json_encode($prizeTypes),
            'bingo_patterns'          => json_encode($bingoPatterns),
            'prize_values'            => json_encode($prizeValues),
            'status'                  => 'ACTIVE',
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    // ─── Command tests ────────────────────────────────────────────────────────

    #[Test]
    public function command_is_registered_and_runs_successfully(): void
    {
        $this->artisan('generate:bingo-rooms')->assertExitCode(0);
    }

    #[Test]
    public function command_logs_execution_on_success(): void
    {
        $this->artisan('generate:bingo-rooms');

        $this->assertDatabaseHas('command_logs', [
            'command' => 'generate:bingo-rooms',
            'status'  => 'SUCCESS',
        ]);
    }

    #[Test]
    public function command_output_mentions_success(): void
    {
        $this->artisan('generate:bingo-rooms')
            ->expectsOutputToContain('exitosamente')
            ->assertExitCode(0);
    }

    // ─── Template filtering ───────────────────────────────────────────────────

    #[Test]
    public function service_returns_false_when_no_active_templates(): void
    {
        $result = app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $this->assertFalse($result);
        $this->assertEquals(0, BingoRoom::count());
    }

    #[Test]
    public function service_skips_inactive_templates(): void
    {
        $this->makeTemplate(['status' => BingoRoomAutomatic::STATUS_INACTIVE]);

        $result = app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $this->assertFalse($result);
        $this->assertEquals(0, BingoRoom::count());
    }

    #[Test]
    public function service_skips_templates_not_scheduled_today(): void
    {
        $now      = Carbon::now('America/Caracas');
        $otherDay = strtolower($now->copy()->addDay()->englishDayOfWeek);

        $this->makeTemplate(['days' => [$otherDay]]);

        $result = app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $this->assertFalse($result);
        $this->assertEquals(0, BingoRoom::count());
    }

    #[Test]
    public function service_skips_templates_outside_time_window(): void
    {
        $futureTime = Carbon::now('America/Caracas')->addHours(2)->format('H:i:s');

        $this->makeTemplate(['time' => $futureTime]);

        $result = app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $this->assertFalse($result);
        $this->assertEquals(0, BingoRoom::count());
    }

    // ─── Room creation ────────────────────────────────────────────────────────

    #[Test]
    public function service_creates_bingo_room_from_active_template(): void
    {
        $template = $this->makeTemplate();

        $result = app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $this->assertTrue($result);
        $this->assertEquals(1, BingoRoom::count());

        $room = BingoRoom::first();
        $inactiveId = GameStatus::where('code', 'INACTIVE')->value('id');
        $this->assertEquals($inactiveId, $room->status_id);
        $this->assertEquals($template->creator_user_id, $room->creator_user_id);
        $this->assertEquals((float) $template->rke_commission, (float) $room->rke_commission);
        $this->assertEquals((float) $template->room_commission, (float) $room->room_commission);
    }

    #[Test]
    public function service_creates_bingo_creator_for_room(): void
    {
        $template = $this->makeTemplate();

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $room    = BingoRoom::first();
        $creator = BingoCreator::where('room_id', $room->id)->first();

        $this->assertNotNull($creator);
        $this->assertEquals($template->creator_user_id, $creator->user_id);
        $this->assertEquals((float) $template->room_commission, (float) $creator->creator_commission);
        $this->assertEquals('ACTIVE', $creator->status);
    }

    #[Test]
    public function service_creates_config_with_creation_automatic(): void
    {
        $this->makeTemplate();

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $config = BingoRoomConfig::first();
        $this->assertEquals('A', $config->creation);
    }

    #[Test]
    public function service_creates_config_with_correct_fields(): void
    {
        $template = $this->makeTemplate([
            'use_base_usd'            => true,
            'card_price_usd'          => 5.00,
            'usd_exchange_round_step' => 0.50,
            'jackpot_pct'             => 20.00,
            'rounds'                  => 2,
            'rounds_pct'              => 80.00,
        ]);

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $config = BingoRoomConfig::first();

        $this->assertNotNull($config);
        $this->assertEquals($template->room_type_id, $config->room_type_id);
        $this->assertEquals($template->game_mode_id, $config->game_mode_id);
        $this->assertEquals($template->prize_pool_type_id, $config->prize_pool_type_id);
        $this->assertTrue((bool) $config->use_base_usd);
        $this->assertEquals(5.00, (float) $config->card_price_usd);
        $this->assertEquals(0.50, (float) $config->usd_exchange_round_step);
        $this->assertEquals(20.00, (float) $config->jackpot_pct);
        $this->assertEquals(2, (int) $config->rounds);
        $this->assertEquals(80.00, (float) $config->rounds_pct);
        $this->assertEquals(
            Carbon::now('America/Caracas')->format('Y-m-d'),
            $config->date->format('Y-m-d')
        );
    }

    #[Test]
    public function service_creates_multiple_rooms_for_multiple_templates(): void
    {
        $this->makeTemplate();
        $this->makeTemplate();

        $result = app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $this->assertTrue($result);
        $this->assertEquals(2, BingoRoom::count());
        $this->assertEquals(2, BingoRoomConfig::count());
    }

    // ─── Card prices ──────────────────────────────────────────────────────────

    #[Test]
    public function service_creates_card_prices_per_currency(): void
    {
        $template   = $this->makeTemplate();
        $currencyId1 = $this->nextId();
        $currencyId2 = $this->nextId();
        $this->addCardPrices($template, [$currencyId1 => 10.00, $currencyId2 => 35000.00]);

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $room = BingoRoom::first();
        $this->assertNotNull($room, 'Room should be created');
        $this->assertEquals(2, BingoCardPrice::where('room_id', $room->id)->count());

        $p1 = BingoCardPrice::where('room_id', $room->id)->where('currency_id', $currencyId1)->first();
        $this->assertEquals(10.00, (float) $p1->card_price);

        $p2 = BingoCardPrice::where('room_id', $room->id)->where('currency_id', $currencyId2)->first();
        $this->assertEquals(35000.00, (float) $p2->card_price);
    }

    #[Test]
    public function service_skips_empty_card_price_entries(): void
    {
        $template    = $this->makeTemplate();
        $currencyId1 = $this->nextId();
        $this->addCardPrices($template, [$currencyId1 => 10.00, $this->nextId() => '']);

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $room = BingoRoom::first();
        $this->assertEquals(1, BingoCardPrice::where('room_id', $room->id)->count());
    }

    #[Test]
    public function service_creates_no_card_prices_when_template_has_none(): void
    {
        $this->makeTemplate();

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $this->assertEquals(0, BingoCardPrice::count());
    }

    // ─── Rounds: PCT ─────────────────────────────────────────────────────────

    #[Test]
    public function service_creates_pct_round_with_prize(): void
    {
        $template  = $this->makeTemplate(['rounds' => 1]);
        $patternId = $this->nextId();
        $this->addRoundConfig($template, ['PCT'], [$patternId], [80.00]);

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $round = BingoRound::first();
        $this->assertNotNull($round);
        $this->assertEquals(1, $round->round);
        $this->assertEquals('PCT', $round->prize_type);
        $this->assertEquals($patternId, $round->bingo_pattern_id);
        $this->assertEquals('ACTIVE', $round->status);

        $prize = BingoRoundPrize::where('bingo_round_id', $round->id)->first();
        $this->assertNotNull($prize);
        $this->assertNull($prize->currency_id);
        $this->assertEquals(80.00, (float) $prize->prize_value);
    }

    // ─── Rounds: FIXED ────────────────────────────────────────────────────────

    #[Test]
    public function service_creates_fixed_round_prizes_per_currency(): void
    {
        $currencyId1 = $this->nextId();
        $currencyId2 = $this->nextId();

        $template = $this->makeTemplate(['rounds' => 1]);
        $this->addRoundConfig(
            $template,
            ['FIXED'],
            [$this->nextId()],
            [[$currencyId1 => 100.00, $currencyId2 => 350000.00]]
        );

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $round = BingoRound::first();
        $this->assertNotNull($round, 'Round should be created');
        $this->assertEquals('FIXED', $round->prize_type);

        $prizes = BingoRoundPrize::where('bingo_round_id', $round->id)->get();
        $this->assertEquals(2, $prizes->count());

        $p1 = $prizes->firstWhere('currency_id', $currencyId1);
        $this->assertEquals(100.00, (float) $p1->prize_value);

        $p2 = $prizes->firstWhere('currency_id', $currencyId2);
        $this->assertEquals(350000.00, (float) $p2->prize_value);
    }

    // ─── Rounds: OTHER ────────────────────────────────────────────────────────

    #[Test]
    public function service_creates_other_round_with_prize_reference(): void
    {
        $prizeId  = $this->nextId();
        $template = $this->makeTemplate(['rounds' => 1]);
        $this->addRoundConfig(
            $template,
            ['OTHER'],
            [$this->nextId()],
            [['prizeId' => $prizeId, 'prizeName' => 'Viaje a París']]
        );

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $round = BingoRound::first();
        $this->assertEquals('OTHER', $round->prize_type);

        $prize = BingoRoundPrize::where('bingo_round_id', $round->id)->first();
        $this->assertNull($prize->currency_id);
        $this->assertEquals($prizeId, $prize->prize_id);
        $this->assertEquals('Viaje a París', $prize->prize_description);
    }

    // ─── Rounds: multiple ────────────────────────────────────────────────────

    #[Test]
    public function service_creates_multiple_rounds_in_correct_order(): void
    {
        $template = $this->makeTemplate(['rounds' => 3]);
        $this->addRoundConfig(
            $template,
            ['PCT', 'PCT', 'PCT'],
            [$this->nextId(), $this->nextId(), $this->nextId()],
            [30.00, 30.00, 40.00]
        );

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $rounds = BingoRound::orderBy('round')->get();
        $this->assertEquals(3, $rounds->count());
        $this->assertEquals(1, $rounds[0]->round);
        $this->assertEquals(2, $rounds[1]->round);
        $this->assertEquals(3, $rounds[2]->round);

        $prizes = BingoRoundPrize::whereIn('bingo_round_id', $rounds->pluck('id'))->get();
        $this->assertEquals(3, $prizes->count());
    }

    #[Test]
    public function service_creates_no_rounds_when_template_has_no_round_config(): void
    {
        $this->makeTemplate(['rounds' => 2]);

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $this->assertEquals(0, BingoRound::count());
    }

    // ─── Serials config ───────────────────────────────────────────────────────

    #[Test]
    public function service_copies_serials_config_to_room_config(): void
    {
        $this->makeTemplate([
            'use_serials' => true,
            'serials_pct' => 15.00,
            'num_serials' => 10,   // varchar(2) in migration — max "99"
        ]);

        app(AutoGenerateBingoRoomsService::class)->createBingoRooms();

        $config = BingoRoomConfig::first();
        $this->assertTrue((bool) $config->use_serials);
        $this->assertEquals(15.00, (float) $config->serials_pct);
        $this->assertEquals(10, (int) $config->num_serials);
    }
}
