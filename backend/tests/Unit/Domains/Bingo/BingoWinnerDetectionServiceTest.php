<?php

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\Services\Game\BingoDrawService;
use App\Exceptions\Domain\ExchangeRate\ExchangeRateException;
use App\Domains\Games\Bingo\Services\Payment\BingoCardPaymentReviewService;
use App\Domains\Games\Bingo\Exceptions\Card\BingoCardPaymentException;
use App\Domains\Games\Bingo\Services\Game\BingoDrawReplayService;
use App\Domains\Games\Bingo\Services\Game\BingoWinnerDetectionService;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Games\Common\ConnectionType\ConnectionType;
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

    protected function setUp() : void
    {
        parent::setUp();

        // El gate de timing exige min_claim_delay_ms desde el último draw;
        // en 0 aísla los tests de detección/no-regresión del gate en sí
        // (el gate se prueba explícitamente en su propio test, más abajo).
        config(['anticheat.bingo.min_claim_delay_ms' => 0]);

        // Esta suite no ejecuta todos los seeders globales; el inicio del sorteo
        // necesita el estado canónico IN_PROGRESS para actualizar también la sala.
        GameStatus::firstOrCreate(['code' => 'IN_PROGRESS'], ['label' => 'En Juego']);
        GameStatus::forgetCache();
    }

    public function test_draw_replay_loads_round_draws_and_orders_equal_timestamps_by_id() : void
    {
        $user = User::factory()->create();
        ['room' => $room, 'round' => $round] = $this->makeRoomWithCard($this->winningCardNumbers(), $user, 'DEMO');

        $this->drawWinningLine($room, $round);

        $timestamp = now()->startOfSecond();
        BingoDraw::where('room_id', (string) $room->id)
            ->where('round_id', (string) $round->id)
            ->update(['time' => 5, 'created_at' => $timestamp, 'updated_at' => $timestamp]);

        $expectedChips = BingoDraw::where('room_id', (string) $room->id)
            ->where('round_id', (string) $round->id)
            ->orderBy('id')
            ->pluck('chip')
            ->map(fn ($chip) => (int) $chip)
            ->all();

        $replay = app(BingoDrawReplayService::class)->replay($room->fresh());
        $actualChips = $replay['rounds'][0]['chips'];

        $this->assertSame($expectedChips, array_column($actualChips, 'chip'));
        $this->assertSame(range(1, count($expectedChips)), array_column($actualChips, 'order'));
    }

    public function test_demo_room_winner_is_recorded_but_prize_is_never_paid() : void
    {
        $user = User::factory()->create();
        ['room' => $room, 'round' => $round] = $this->makeRoomWithCard($this->winningCardNumbers(), $user, 'DEMO');
        // Bote "de mentira" (ej. Valor Fijo de una sala DEMO): no debe acreditarse.
        $room->update(['prize_pool' => 100, 'full_house_prize' => 100]);

        $this->drawWinningLine($room, $round);

        app(BingoWinnerDetectionService::class)->checkWinnerForUser($room->fresh(), (int) $user->id);

        $this->assertDatabaseHas('bingo_card_prizes', [
            'room_id' => (string) $room->id,
            'prize_status' => 'cancelled',
        ]);
        $this->assertDatabaseMissing('bingo_card_prizes', [
            'room_id' => (string) $room->id,
            'prize_status' => 'claimed',
        ]);
        $this->assertDatabaseCount('wallet_movements', 0);
    }

    /**
     * BingoDrawService es `final` (por diseño, ver GamePrizeService — centraliza
     * pagos atómicos y no debe subclasificarse/mockearse). Estos tests resuelven
     * BingoWinnerDetectionService completo vía el contenedor real en vez de
     * mockear su dependencia, y verifican solo lo que le compete al gate
     * anti-cheat: si bloquea o no, y si crea/omite el flag correspondiente.
     */
    public function test_room_is_considered_started_when_round_is_in_progress_before_scheduled_time() : void
    {
        $user = User::factory()->create();
        ['room' => $room, 'round' => $round] = $this->makeRoomWithCard($this->winningCardNumbers(), $user, 'DEMO');

        // Una sala iniciada manualmente puede empezar antes de unix_date.
        $room->config->update(['unix_date' => now()->addHour()->timestamp]);
        $round->update(['status' => 'IN_PROGRESS', 'start_time' => now()]);

        $this->assertTrue($room->fresh()->hasStarted());
    }

    public function test_draws_from_a_finished_round_cannot_complete_the_active_round() : void
    {
        $user = User::factory()->create();
        ['room' => $room, 'round' => $previousRound] =
            $this->makeRoomWithCard($this->winningCardNumbers(), $user, 'DEMO');

        $activeRound = BingoRound::create([
            'room_id' => $room->id,
            'room_config_id' => $room->config->id,
            'round' => 2,
            'prize_type' => 'PCT',
            'bingo_pattern_id' => $previousRound->bingo_pattern_id,
            'status' => 'ACTIVE',
        ]);

        $previousRound->update(['status' => 'FINISHED']);
        $this->drawWinningLine($room, $previousRound);

        $result = app(BingoWinnerDetectionService::class)
            ->checkWinnerForUser($room->fresh(), (int) $user->id);

        $this->assertNull($result);
        $this->assertSame('ACTIVE', $activeRound->fresh()->status);
        $this->assertSame(BingoCard::STATUS_ACTIVE, (int) BingoCard::first()->status);
        $this->assertDatabaseCount('bingo_card_prizes', 0);
    }

    public function test_impossible_claim_timing_returns_null_and_creates_flag_without_touching_prizes() : void
    {
        // Umbral deliberadamente alto (60s): el overhead real de MySQL entre
        // crear la sala/cartón/draw (varios inserts secuenciales) y llamar al
        // servicio ya consume cientos de ms — un umbral de 400ms sería
        // indistinguible de ese overhead y volvería el test no determinista.
        config(['anticheat.bingo.min_claim_delay_ms' => 60_000]);

        $user = User::factory()->create();
        ['room' => $room, 'round' => $round] = $this->makeRoomWithCard($this->winningCardNumbers(), $user);

        $this->drawWinningLine($room, $round);

        $service = app(BingoWinnerDetectionService::class);

        // El gate hace return early antes de tocar BingoDrawService — nunca se
        // llega a claimCardWin(), por lo que no hace falta mockearlo aquí.
        $result = $service->checkWinnerForUser($room->fresh(), (int) $user->id);

        $this->assertNull($result);
        $this->assertDatabaseHas('anti_cheat_flags', [
            'user_id' => (string) $user->id,
            'game_slug' => 'bingo',
            'signal_type' => 'IMPOSSIBLE_CLAIM_TIMING',
        ]);
        $this->assertDatabaseCount('bingo_card_prizes', 0);
    }

    public function test_normal_timing_with_winning_card_does_not_block_at_the_gate() : void
    {
        $user = User::factory()->create();
        ['room' => $room, 'round' => $round] = $this->makeRoomWithCard($this->winningCardNumbers(), $user);

        $this->drawWinningLine($room, $round);

        $service = app(BingoWinnerDetectionService::class);

        // No se asevera sobre el resultado de premio (responsabilidad de
        // BingoDrawService, cubierta en sus propios tests) — solo que el gate
        // de timing no interfiere y no genera un flag de falso positivo.
        $service->checkWinnerForUser($room->fresh(), (int) $user->id);

        $this->assertDatabaseMissing('anti_cheat_flags', [
            'user_id' => (string) $user->id,
            'signal_type' => 'IMPOSSIBLE_CLAIM_TIMING',
        ]);
    }

    public function test_latest_draw_is_deterministic_when_multiple_draws_share_the_same_timestamp() : void
    {
        config(['anticheat.bingo.min_claim_delay_ms' => 60_000]);

        $user = User::factory()->create();
        ['room' => $room, 'round' => $round] = $this->makeRoomWithCard($this->winningCardNumbers(), $user);

        $this->drawWinningLine($room, $round);

        // MySQL DATETIME precision can make several chips share one timestamp.
        // In that case the Snowflake ID is the deterministic insertion-order tie-breaker.
        $draws = BingoDraw::where('round_id', (string) $round->id)->get();
        $sameTimestamp = now()->startOfSecond();
        BingoDraw::where('round_id', (string) $round->id)->update([
            'created_at' => $sameTimestamp,
            'updated_at' => $sameTimestamp,
        ]);
        $expectedLastDrawId = (string) $draws->max('id');

        app(BingoWinnerDetectionService::class)->checkWinnerForUser($room->fresh(), (int) $user->id);

        $flag = \Illuminate\Support\Facades\DB::table('anti_cheat_flags')
            ->where('user_id', (string) $user->id)
            ->where('signal_type', 'IMPOSSIBLE_CLAIM_TIMING')
            ->latest('id')
            ->first();

        $this->assertNotNull($flag);
        $metadata = json_decode($flag->metadata, true);
        $this->assertSame($expectedLastDrawId, (string) $metadata['last_draw_id']);
    }

    public function test_normal_timing_without_winning_card_returns_null_without_flagging() : void
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

        $service = app(BingoWinnerDetectionService::class);

        $result = $service->checkWinnerForUser($room->fresh(), (int) $user->id);

        $this->assertNull($result);
        $this->assertDatabaseMissing('anti_cheat_flags', [
            'user_id' => (string) $user->id,
        ]);
    }

    public function test_pending_payment_cannot_be_approved_without_confirmation_after_draw_starts() : void
    {
        $user = User::factory()->create();
        ['room' => $room, 'round' => $round, 'card' => $card] =
            $this->makeRoomWithCard($this->winningCardNumbers(), $user, 'DEMO');

        BingoCardPayment::create([
            'payment_method' => 'mobile_payment',
            'description' => 'Comprobante pendiente de revisión',
            'room_id' => $room->id,
            'seller_user_id' => $user->id,
            'user_id' => $user->id,
            'currency_id' => $card->currency_id,
            'card_lot_id' => $card->card_lot_id,
            'payment_amount' => 1,
            'payment_proof' => 'private/receipts/bingo/test.jpg',
            'payment_status' => 'PENDING',
        ]);

        // Ejecutar las 75 fichas sin esperas: el intervalo 0 solo se usa en este test.
        config(['app.env' => 'local']);
        putenv('BINGO_DEV_INTERVAL_SECONDS=0');
        $room->config->update(['time_chip' => 0]);

        try {
            app(BingoDrawService::class)->startDraw($room->fresh());
        } finally {
            putenv('BINGO_DEV_INTERVAL_SECONDS');
        }

        $this->assertSame('FINISHED', $round->fresh()->status);

        $this->expectException(BingoCardPaymentException::class);
        app(BingoCardPaymentReviewService::class)->approve(
            BingoCardPayment::where('card_lot_id', $card->card_lot_id)->firstOrFail(),
        );
    }

    public function test_pending_payment_cannot_be_approved_after_its_lot_was_refunded() : void
    {
        $user = User::factory()->create();
        ['room' => $room, 'card' => $card] =
            $this->makeRoomWithCard($this->winningCardNumbers(), $user, 'DEMO');

        $payment = BingoCardPayment::create([
            'payment_method' => 'mobile_payment',
            'description' => 'Comprobante pendiente de revisión',
            'room_id' => $room->id,
            'seller_user_id' => $user->id,
            'user_id' => $user->id,
            'currency_id' => $card->currency_id,
            'card_lot_id' => $card->card_lot_id,
            'payment_amount' => 1,
            'payment_proof' => 'private/receipts/bingo/test.jpg',
            'payment_status' => 'PENDING',
        ]);

        \App\Domains\Games\Bingo\Models\Card\BingoCardLot::whereKey($card->card_lot_id)
            ->update(['status' => 'REFUNDED']);

        try {
            app(BingoCardPaymentReviewService::class)->approve($payment);
            $this->fail('No se debe aprobar un pago cuyo lote ya fue reembolsado.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('lote de cartones', $e->getMessage());
        }

        $this->assertSame('PENDING', $payment->fresh()->payment_status);
    }

    public function test_percentage_prize_is_not_calculated_without_an_active_currency_rate() : void
    {
        $user = User::factory()->create();
        ['room' => $room, 'round' => $round, 'pattern' => $pattern, 'card' => $card] =
            $this->makeRoomWithCard($this->winningCardNumbers(), $user);

        $currency = Currency::firstOrCreate(
            ['iso_code' => 'B' . strtoupper(Str::random(2))],
            ['name' => 'Bingo FX Test', 'type' => 'FIAT'],
        );

        $card->update(['currency_id' => $currency->id]);
        $card->cardLot()->update(['currency_id' => $currency->id]);
        $room->update(['prize_pool' => 100]);

        $this->drawWinningLine($room, $round);
        $lastDrawId = (int) BingoDraw::where('round_id', $round->id)->orderByDesc('created_at')->value('id');

        try {
            app(BingoDrawService::class)->claimCardWin(
                $room->fresh(),
                $round->fresh(),
                $pattern->fresh(),
                (string) $card->id,
                $lastDrawId,
            );
            $this->fail('El premio no debe calcularse con una tasa 1:1 implícita.');
        } catch (ExchangeRateException $e) {
            $this->assertStringContainsString('No hay tasa de cambio activa', $e->getMessage());
        }

        $this->assertSame(BingoCard::STATUS_ACTIVE, (int) $card->fresh()->status);
        $this->assertDatabaseMissing('bingo_card_prizes', [
            'winning_card_id' => (string) $card->id,
            'round_id' => (string) $round->id,
        ]);
    }

    public function test_pending_payment_carton_cannot_be_claimed_manually_or_atomically() : void
    {
        $user = User::factory()->create();
        ['room' => $room, 'round' => $round, 'pattern' => $pattern, 'card' => $card] =
            $this->makeRoomWithCard($this->winningCardNumbers(), $user, 'DEMO');

        $this->drawWinningLine($room, $round);
        $lastDrawId = (int) BingoDraw::where('round_id', $round->id)->orderByDesc('created_at')->value('id');

        $payment = BingoCardPayment::create([
            'payment_method' => 'mobile_payment',
            'description' => 'Comprobante pendiente de revisión',
            'room_id' => $room->id,
            'seller_user_id' => $user->id,
            'user_id' => $user->id,
            'currency_id' => $card->currency_id,
            'card_lot_id' => $card->card_lot_id,
            'payment_amount' => 1,
            'payment_proof' => 'private/receipts/bingo/test.jpg',
            'payment_status' => 'PENDING',
        ]);

        $manualResult = app(BingoWinnerDetectionService::class)
            ->checkWinnerForUser($room->fresh(), (int) $user->id);

        $atomicResult = app(BingoDrawService::class)->claimCardWin(
            $room,
            $round,
            $pattern,
            (string) $card->id,
            $lastDrawId,
        );

        $this->assertNull($manualResult);
        $this->assertNull($atomicResult);

        // Soft-delete no debe permitir que un pago pendiente desaparezca del gate.
        $payment->delete();

        $deletedPaymentManualResult = app(BingoWinnerDetectionService::class)
            ->checkWinnerForUser($room->fresh(), (int) $user->id);

        $deletedPaymentAtomicResult = app(BingoDrawService::class)->claimCardWin(
            $room,
            $round,
            $pattern,
            (string) $card->id,
            $lastDrawId,
        );

        $this->assertNull($deletedPaymentManualResult);
        $this->assertNull($deletedPaymentAtomicResult);
        $this->assertSame(BingoCard::STATUS_ACTIVE, (int) $card->fresh()->status);
        $this->assertDatabaseCount('bingo_card_prizes', 0);
    }

    private function drawWinningLine(BingoRoom $room, BingoRound $round) : void
    {
        foreach (['1', '16', '31', '46', '61'] as $chip) {
            BingoDraw::create([
                'room_id' => $room->id,
                'round_id' => $round->id,
                'chip' => $chip,
                'code' => 'CODE' . $chip,
                'time' => 5,
            ]);
        }
    }

    /**
     * Arma sala + config + ronda + patrón (línea simple: fila 0 de cada
     * columna) + cartón activo del usuario, listos para checkWinnerForUser().
     */
    private function makeRoomWithCard(array $cardNumbers, User $user, string $roomTypeCode = 'PUBLIC') : array
    {
        $country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO'],
        );

        $status = GameStatus::firstOrCreate(['code' => 'ACTIVE'], ['label' => 'Activa']);
        $roomType = RoomType::firstOrCreate(['code' => $roomTypeCode], ['label' => $roomTypeCode]);
        $connectionType = ConnectionType::firstOrCreate(['code' => 'ONLINE'], ['label' => 'Online']);
        // game_id es NOT NULL (prize_pool_types es per-game, ver PrizePoolTypeSeeder).
        $game = Game::firstOrCreate(['name' => 'BINGO'], ['status' => 'ACTIVE']);
        $prizePoolType = PrizePoolType::firstOrCreate(
            ['game_id' => $game->id, 'code' => 'STANDARD'],
            ['label' => 'Estándar', 'is_accumulative' => false],
        );
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        $room = BingoRoom::create([
            'uuid_code' => (string) Str::uuid(),
            'status_id' => $status->id,
            'creator_user_id' => $user->id,
        ]);

        BingoRoomConfig::create([
            'room_id' => $room->id,
            'room_type_id' => $roomType->id,
            'creator_user_id' => $user->id,
            'connection_type_id' => $connectionType->id,
            'prize_pool_type_id' => $prizePoolType->id,
            'game_variant' => BingoVariant::BINGO_75,
        ]);

        $roomUser = BingoRoomUser::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'cards_purchased' => 1,
        ]);

        $lot = BingoCardLot::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'room_user_id' => $roomUser->id,
            'currency_id' => $currency->id,
            'card_count' => 1,
            'card_price' => 1,
            'exchange_rate' => 1,
            'sale_amount' => 1,
        ]);

        $pattern = BingoPattern::create([
            // num_id es unsignedTinyInteger (0-255) en la migración de bingo_patterns.
            'num_id' => random_int(100, 250),
            'type_pattern' => 'UNIQUE',
            'name' => 'Línea simple test',
            'user_id' => null,
            'boxes' => 'B1;I1;N1;G1;O1', // fila índice 0 de cada columna
            'status' => 'ACTIVE',
        ]);

        $round = BingoRound::create([
            'room_id' => $room->id,
            'room_config_id' => $room->config->id,
            'round' => 1,
            'prize_type' => 'PCT',
            'bingo_pattern_id' => $pattern->id,
            'status' => 'ACTIVE',
        ]);

        $card = BingoCard::create([
            'room_id' => $room->id,
            'creator_user_id' => $user->id,
            'room_user_id' => $roomUser->id,
            'card_lot_id' => $lot->id,
            'currency_id' => $currency->id,
            'serial_number' => 'SN-' . Str::random(10),
            'card_numbers' => $cardNumbers,
            'card_hash' => hash('sha256', Str::random(20)),
            'status' => BingoCard::STATUS_ACTIVE,
        ]);

        return compact('room', 'round', 'pattern', 'card');
    }

    private function winningCardNumbers() : array
    {
        return [
            'B' => [1, 2, 3, 4, 5],
            'I' => [16, 17, 18, 19, 20],
            'N' => [31, 32, null, 34, 35],
            'G' => [46, 47, 48, 49, 50],
            'O' => [61, 62, 63, 64, 65],
        ];
    }
}
