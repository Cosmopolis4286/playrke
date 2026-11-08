<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\Services\Game\BingoDrawService;
use App\Domains\Games\Bingo\Services\Game\BingoPatternPositionResolver;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Models\Currency;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integridad del sorteo automático (BingoDrawService):
 *  - Cartones de lotes con pago PENDING/REJECTED no participan ni ganan.
 *  - Si un reclamo manual ("¡BINGO!") ya registró el premio de la ronda, el
 *    sorteo cierra ronda/sala sin crear un segundo premio.
 *
 * El patrón "solo casilla libre" (N3) lo completa cualquier cartón con la
 * primera ficha: el sorteo termina antes del bucle de fichas (que duerme entre
 * fichas) y el resultado es determinista pese a la secuencia aleatoria.
 */
class BingoDrawIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    private string $currencyId;
    private string $previousSqlMode = '';

    protected function setUp() : void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        // Columnas NOT NULL sin default que el sorteo no usa (comprobante, etc.):
        // mismo enfoque que BingoFinancialSettlementTest.
        $this->previousSqlMode = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
        DB::statement("SET SESSION sql_mode = ''");
        Cache::flush();
        GameStatus::forgetCache();

        foreach (['ACTIVE', 'IN_PROGRESS', 'FINISHED'] as $code) {
            GameStatus::firstOrCreate(['code' => $code], ['label' => $code]);
        }

        $this->currencyId = (string) Currency::firstOrCreate(
            ['iso_code' => 'USD'],
            ['symbol' => '$', 'name' => 'US Dollar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE'],
        )->id;
    }

    protected function tearDown() : void
    {
        DB::statement('SET SESSION sql_mode = ?', [$this->previousSqlMode]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    #[Test]
    public function cartones_con_pago_pendiente_o_rechazado_no_cuentan_como_activos() : void
    {
        ['room' => $room, 'pattern' => $pattern] = $this->makeRoom('B1');

        $approved = $this->makeCard($room, 'APPROVED');
        $withoutPayment = $this->makeCard($room, null);
        $pending = $this->makeCard($room, 'PENDING');
        // Cartón ACTIVE de un lote REJECTED (normalmente se cancelan al rechazar;
        // el filtro no depende de esa actualización).
        $rejected = $this->makeCard($room, 'REJECTED');

        $positions = (new BingoPatternPositionResolver)->resolve($pattern, BingoVariant::BINGO_75);
        $players = app(BingoDrawService::class)->computePlayersProgress($room, $positions, []);
        $userIds = array_column($players, 'user_id');

        $this->assertEqualsCanonicalizing(
            [(int) $approved->creator_user_id, (int) $withoutPayment->creator_user_id],
            $userIds,
        );
        $this->assertNotContains((int) $pending->creator_user_id, $userIds);
        $this->assertNotContains((int) $rejected->creator_user_id, $userIds);
    }

    #[Test]
    public function cartón_con_pago_pendiente_no_gana_en_el_sorteo() : void
    {
        ['room' => $room, 'round' => $round] = $this->makeRoom('N3');

        $pending = $this->makeCard($room, 'PENDING');
        $approved = $this->makeCard($room, 'APPROVED');

        $result = app(BingoDrawService::class)->startDraw($room->fresh());

        $this->assertTrue($result['success']);
        $this->assertSame([(string) $approved->id], array_column($result['winners'], 'card_id'));
        $this->assertSame(BingoCard::STATUS_ACTIVE, (int) $pending->fresh()->status);
        $this->assertFalse(BingoCardPrize::where('winning_card_id', $pending->id)->exists());
        $this->assertSame(1, BingoCardPrize::where('round_id', $round->id)->count());
    }

    /**
     * Una sala ya iniciada se retoma aunque haya quedado por debajo de los
     * mínimos (p. ej. se rechazaron pagos pendientes al inicio): los mínimos
     * solo deciden si arranca.
     */
    #[Test]
    public function el_chequeo_de_salud_no_bloquea_retomar_por_los_minimos() : void
    {
        ['room' => $room, 'round' => $round] = $this->makeRoom('N3');
        $room->config->update(['min_players' => 5, 'min_cards_to_start' => 10]);
        $this->makeCard($room, 'APPROVED');
        $health = app(\App\Domains\Games\Bingo\Services\Game\BingoRoomHealthCheckService::class);

        // Sala IN_PROGRESS con la ronda en curso: se retoma.
        $round->update(['status' => 'IN_PROGRESS']);
        $this->assertSame([], $health->collectErrors($room->fresh()));

        // Sala que todavía no arrancó: los mínimos sí aplican.
        $round->update(['status' => 'ACTIVE']);
        $room->update(['status_id' => GameStatus::codeToId('ACTIVE')]);
        $errors = $health->collectErrors($room->fresh());
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('mínimo', implode(' ', $errors));
    }

    #[Test]
    public function lock_con_latido_reciente_o_sin_latido_se_respeta() : void
    {
        ['room' => $room] = $this->makeRoom('N3');
        $this->makeCard($room, 'APPROVED');
        Cache::lock("bingo:draw-lock:{$room->id}", 5400)->get();

        // Sin latido (lock de una versión anterior): se respeta.
        $this->assertSame('lock_held', app(BingoDrawService::class)->startDraw($room->fresh())['reason'] ?? null);

        // Latido reciente: el dueño sigue sorteando.
        Cache::put("bingo:draw-heartbeat:{$room->id}", now()->timestamp, now()->addDay());
        $this->assertSame('lock_held', app(BingoDrawService::class)->startDraw($room->fresh())['reason'] ?? null);
        $this->assertSame(0, BingoCardPrize::where('room_id', $room->id)->count());
    }

    /**
     * El worker que tenía el lock murió (sin latido reciente): el sorteo se
     * retoma en vez de esperar a que venza el lock (mínimo 90 min).
     */
    #[Test]
    public function lock_sin_latido_se_libera_y_el_sorteo_se_retoma() : void
    {
        ['room' => $room] = $this->makeRoom('N3');
        $this->makeCard($room, 'APPROVED');

        Cache::lock("bingo:draw-lock:{$room->id}", 5400)->get();
        Cache::put("bingo:draw-heartbeat:{$room->id}", now()->subMinutes(10)->timestamp, now()->addDay());

        $result = app(BingoDrawService::class)->startDraw($room->fresh());

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['winners'] ?? []);
    }

    /**
     * Carrera: el reclamo manual (check-bingo → claimCardWin) paga primero.
     * Antes el sorteo seguía cantando fichas (claimCardWin devolvía null para el
     * premio ya pagado) y podía premiar a otro cartón; ahora cierra ronda y sala.
     */
    #[Test]
    public function si_la_ronda_ya_tiene_premio_el_sorteo_cierra_sin_crear_otro() : void
    {
        ['room' => $room, 'round' => $round, 'pattern' => $pattern] = $this->makeRoom('N3');

        $manualWinner = $this->makeCard($room, 'APPROVED');
        $other = $this->makeCard($room, 'APPROVED');

        $service = app(BingoDrawService::class);
        $claimed = $service->claimCardWin($room->fresh(), $round, $pattern, (string) $manualWinner->id, 0);
        $this->assertNotNull($claimed);

        $result = $service->startDraw($room->fresh());

        $this->assertTrue($result['success']);
        $this->assertTrue($result['alreadyFinished'] ?? false);
        $this->assertSame([(string) $manualWinner->id], array_column($result['winners'], 'card_id'));

        $this->assertSame(1, BingoCardPrize::where('round_id', $round->id)->count());
        $this->assertSame(BingoCard::STATUS_ACTIVE, (int) $other->fresh()->status);
        $this->assertSame('FINISHED', $round->fresh()->status);
        $this->assertSame('FINISHED', $room->fresh()->load('status')->status->code);
    }

    /** Cartón ACTIVE de un jugador propio, en su propio lote, con el pago indicado (null = sin fila de pago). */
    private function makeCard(BingoRoom $room, ?string $paymentStatus) : BingoCard
    {
        $user = User::factory()->create();

        $roomUser = BingoRoomUser::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => BingoRoomUser::ROLE_PLAYER,
            'cards_purchased' => 1,
            'winner' => 'N',
        ]);

        $lot = BingoCardLot::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'room_user_id' => $roomUser->id,
            'currency_id' => $this->currencyId,
            'card_count' => 1,
            'card_price' => 1,
            'exchange_rate' => 1,
            'sale_amount' => 1,
        ]);

        if ($paymentStatus !== null) {
            BingoCardPayment::create([
                'payment_method' => 'BANK_TRANSFER',
                'room_id' => $room->id,
                'seller_user_id' => $room->creator_user_id,
                'user_id' => $user->id,
                'currency_id' => $this->currencyId,
                'card_lot_id' => $lot->id,
                'payment_amount' => 1,
                'payment_status' => $paymentStatus,
            ]);
        }

        return BingoCard::create([
            'room_id' => $room->id,
            'creator_user_id' => $user->id,
            'room_user_id' => $roomUser->id,
            'card_lot_id' => $lot->id,
            'currency_id' => $this->currencyId,
            'serial_number' => 'SN-' . Str::random(10),
            'card_numbers' => [
                'B' => [1, 2, 3, 4, 5],
                'I' => [16, 17, 18, 19, 20],
                'N' => [31, 32, null, 34, 35],
                'G' => [46, 47, 48, 49, 50],
                'O' => [61, 62, 63, 64, 65],
            ],
            'card_hash' => hash('sha256', Str::random(20)),
            'status' => BingoCard::STATUS_ACTIVE,
        ]);
    }

    /** @return array{room: BingoRoom, round: BingoRound, pattern: BingoPattern} */
    private function makeRoom(string $boxes) : array
    {
        $creator = User::factory()->create();

        $room = BingoRoom::create([
            'uuid_code' => (string) Str::uuid(),
            'status_id' => GameStatus::codeToId('IN_PROGRESS'),
            'creator_user_id' => $creator->id,
            'prize_pool' => 0,
        ]);

        // FKs de tipo de sala/conexión/bote sintéticos (FOREIGN_KEY_CHECKS=0):
        // el sorteo no los consulta.
        $config = BingoRoomConfig::create([
            'room_id' => $room->id,
            'room_type_id' => '960000000000000001',
            'creator_user_id' => $creator->id,
            'connection_type_id' => '960000000000000002',
            'prize_pool_type_id' => '960000000000000003',
            'game_variant' => BingoVariant::BINGO_75,
            'rounds' => '1',
            'time_chip' => '3',
        ]);

        $pattern = BingoPattern::create([
            'num_id' => random_int(100, 250),
            'type_pattern' => 'UNIQUE',
            'name' => 'Patrón test ' . $boxes,
            'user_id' => null,
            'boxes' => $boxes,
            'status' => 'ACTIVE',
        ]);

        $round = BingoRound::create([
            'room_id' => $room->id,
            'room_config_id' => $config->id,
            'round' => 1,
            'prize_type' => 'PCT',
            'bingo_pattern_id' => $pattern->id,
            'status' => 'ACTIVE',
        ]);

        return ['room' => $room->fresh(), 'round' => $round, 'pattern' => $pattern];
    }
}
