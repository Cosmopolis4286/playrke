<?php

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\Services\Game\BingoDrawService;
use App\Domains\Games\Bingo\Services\Game\BingoWinnerDetectionService;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Games\Common\GameMode\GameMode;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Room\RoomType;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BingoWinnerDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El gate de timing exige min_claim_delay_ms desde el último draw;
        // en 0 aísla los tests de detección/no-regresión del gate en sí
        // (el gate se prueba explícitamente en su propio test, más abajo).
        config(['anticheat.bingo.min_claim_delay_ms' => 0]);
    }

    /**
     * Arma sala + config + ronda + patrón (línea simple: fila 0 de cada
     * columna) + cartón activo del usuario, listos para checkWinnerForUser().
     */
    private function makeRoomWithCard(array $cardNumbers, User $user): array
    {
        $country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO'],
        );

        $status = GameStatus::firstOrCreate(['code' => 'ACTIVE'], ['label' => 'Activa']);
        $roomType = RoomType::firstOrCreate(['code' => 'PUBLIC'], ['label' => 'Pública']);
        $gameMode = GameMode::firstOrCreate(['code' => 'STANDARD'], ['label' => 'Estándar']);
        $prizePoolType = PrizePoolType::firstOrCreate(['code' => 'STANDARD'], ['label' => 'Estándar', 'is_accumulative' => false]);
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        $room = BingoRoom::create([
            'uuid_code'       => (string) Str::uuid(),
            'status_id'       => $status->id,
            'creator_user_id' => $user->id,
        ]);

        BingoRoomConfig::create([
            'room_id'            => $room->id,
            'room_type_id'       => $roomType->id,
            'creator_user_id'    => $user->id,
            'game_mode_id'       => $gameMode->id,
            'prize_pool_type_id' => $prizePoolType->id,
            'game_variant'       => BingoVariant::BINGO_75,
        ]);

        $roomUser = BingoRoomUser::create([
            'room_id'         => $room->id,
            'user_id'         => $user->id,
            'cards_purchased' => 1,
        ]);

        $lot = BingoCardLot::create([
            'room_id'       => $room->id,
            'user_id'       => $user->id,
            'room_user_id'  => $roomUser->id,
            'currency_id'   => $currency->id,
            'card_count'    => 1,
            'card_price'    => 1,
            'exchange_rate' => 1,
            'sale_amount'   => 1,
        ]);

        $pattern = BingoPattern::create([
            'num_id'       => random_int(1000, 999999),
            'type_pattern' => 'UNIQUE',
            'name'         => 'Línea simple test',
            'user_id'      => null,
            'boxes'        => 'B1;I1;N1;G1;O1', // fila índice 0 de cada columna
            'status'       => 'ACTIVE',
        ]);

        $round = BingoRound::create([
            'room_id'         => $room->id,
            'room_config_id'  => $room->config->id,
            'round'           => 1,
            'prize_type'      => 'PCT',
            'bingo_pattern_id'=> $pattern->id,
            'status'          => 'ACTIVE',
        ]);

        $card = BingoCard::create([
            'room_id'         => $room->id,
            'creator_user_id' => $user->id,
            'room_user_id'    => $roomUser->id,
            'card_lot_id'     => $lot->id,
            'currency_id'     => $currency->id,
            'serial_number'   => 'SN-' . Str::random(10),
            'card_numbers'    => $cardNumbers,
            'card_hash'       => hash('sha256', Str::random(20)),
            'status'          => BingoCard::STATUS_ACTIVE,
        ]);

        return compact('room', 'round', 'pattern', 'card');
    }

    private function winningCardNumbers(): array
    {
        return [
            'B' => [1, 2, 3, 4, 5],
            'I' => [16, 17, 18, 19, 20],
            'N' => [31, 32, null, 34, 35],
            'G' => [46, 47, 48, 49, 50],
            'O' => [61, 62, 63, 64, 65],
        ];
    }

    private function drawWinningLine(BingoRoom $room, BingoRound $round): void
    {
        foreach (['1', '16', '31', '46', '61'] as $chip) {
            BingoDraw::create([
                'room_id'  => $room->id,
                'round_id' => $round->id,
                'chip'     => $chip,
                'code'     => 'CODE' . $chip,
                'time'     => 5,
            ]);
        }
    }

    public function test_impossible_claim_timing_returns_null_and_creates_flag_without_touching_prizes(): void
    {
        config(['anticheat.bingo.min_claim_delay_ms' => 400]);

        $user = User::factory()->create();
        ['room' => $room, 'round' => $round] = $this->makeRoomWithCard($this->winningCardNumbers(), $user);

        $this->drawWinningLine($room, $round);

        $drawService = $this->createMock(BingoDrawService::class);
        $drawService->expects($this->never())->method('claimCardWin');

        $service = new BingoWinnerDetectionService($drawService, app(\App\Services\Games\AntiCheatService::class));

        // Sin esperar: el último draw se acaba de crear, elapsed < 400ms.
        $result = $service->checkWinnerForUser($room->fresh(), (int) $user->id);

        $this->assertNull($result);
        $this->assertDatabaseHas('anti_cheat_flags', [
            'user_id'     => (string) $user->id,
            'game_slug'   => 'bingo',
            'signal_type' => 'IMPOSSIBLE_CLAIM_TIMING',
        ]);
        $this->assertDatabaseCount('bingo_card_prizes', 0);
    }

    public function test_normal_timing_with_winning_card_still_works(): void
    {
        $user = User::factory()->create();
        ['room' => $room, 'round' => $round, 'pattern' => $pattern, 'card' => $card] =
            $this->makeRoomWithCard($this->winningCardNumbers(), $user);

        $this->drawWinningLine($room, $round);

        $expectedWinner = ['winner' => true];

        $drawService = $this->createMock(BingoDrawService::class);
        $drawService->expects($this->once())
            ->method('claimCardWin')
            ->with(
                $this->callback(fn ($r) => (string) $r->id === (string) $room->id),
                $this->callback(fn ($r) => (string) $r->id === (string) $round->id),
                $this->callback(fn ($p) => (string) $p->id === (string) $pattern->id),
                (string) $card->id,
                $this->anything(),
            )
            ->willReturn($expectedWinner);

        $service = new BingoWinnerDetectionService($drawService, app(\App\Services\Games\AntiCheatService::class));

        $result = $service->checkWinnerForUser($room->fresh(), (int) $user->id);

        $this->assertSame($expectedWinner, $result);
        $this->assertDatabaseMissing('anti_cheat_flags', [
            'user_id'     => (string) $user->id,
            'signal_type' => 'IMPOSSIBLE_CLAIM_TIMING',
        ]);
    }

    public function test_normal_timing_without_winning_card_returns_null_without_flagging(): void
    {
        $user = User::factory()->create();

        // Cartón que NO coincide con el patrón (números distintos a los cantados).
        $losingCardNumbers = [
            'B' => [10, 11, 12, 13, 14],
            'I' => [25, 26, 27, 28, 29],
            'N' => [40, 41, null, 43, 44],
            'G' => [55, 56, 57, 58, 59],
            'O' => [70, 71, 72, 73, 74],
        ];

        ['room' => $room, 'round' => $round] = $this->makeRoomWithCard($losingCardNumbers, $user);

        $this->drawWinningLine($room, $round); // canta la línea del cartón GANADOR, no del perdedor

        $drawService = $this->createMock(BingoDrawService::class);
        $drawService->expects($this->never())->method('claimCardWin');

        $service = new BingoWinnerDetectionService($drawService, app(\App\Services\Games\AntiCheatService::class));

        $result = $service->checkWinnerForUser($room->fresh(), (int) $user->id);

        $this->assertNull($result);
        $this->assertDatabaseMissing('anti_cheat_flags', [
            'user_id' => (string) $user->id,
        ]);
    }
}
