<?php

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Metrics\RoomMetricsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * RoomMetricsService::updateRoomMetricsWithCommissions():
 *  - El reparto de la venta es el mismo para todo tipo de bote, incluido
 *    PRIZE (antes había un `!= 2` muerto pensado para excluirlo).
 *  - El aporte al jackpot se descuenta del bote también en el desglose por
 *    moneda (metrics_by_currency), no solo en USD.
 */
class RoomMetricsServicePrizePoolTypeTest extends TestCase
{
    use DatabaseTransactions;

    private static int $seq = 0;
    private string $previousSqlMode = '';
    private string $currencyId = '930000000000000001';

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->previousSqlMode = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
        DB::statement("SET SESSION sql_mode = ''");
        // PlatformWalletService/JackpotLedgerService son final (no mockeables); el
        // servicio ya los envuelve en try/catch y DatabaseTransactions revierte lo que escriban.
    }

    protected function tearDown(): void
    {
        DB::statement('SET SESSION sql_mode = ?', [$this->previousSqlMode]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    private function makeRoom(string $poolCode, ?float $jackpotPct = null): BingoRoom
    {
        $n = ++self::$seq;
        $typeId = (string) (930000000000000100 + $n);
        $roomId = (string) (930000000000000200 + $n);

        DB::table('prize_pool_types')->insert(['id' => $typeId, 'game_id' => '1', 'code' => $poolCode, 'label' => $poolCode, 'status' => 'ACTIVE']);
        DB::table('bingo_rooms')->insert([
            'id' => $roomId, 'status_id' => '1', 'creator_user_id' => '1', 'paid_prizes' => 'N',
            'uuid_code' => 'TEST-' . $roomId, 'prize_pool' => 0, 'total_cards' => 0, 'total_sales' => 0,
            'rke_commission' => 0, 'room_commission' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('bingo_rooms_config')->insert([
            'id' => (string) ((int) $roomId + 1000), 'room_id' => $roomId,
            'prize_pool_type_id' => $typeId, 'rke_commission' => 5, 'room_commission' => 10,
            'jackpot_type_id' => $jackpotPct ? '930000000000000999' : null, 'jackpot_pct' => $jackpotPct,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return BingoRoom::find($roomId);
    }

    private function sell(BingoRoom $room, float $amount): void
    {
        $ok = app(RoomMetricsService::class)->updateRoomMetricsWithCommissions(
            $room,
            ['cardCount' => 1, 'currency' => $this->currencyId, 'creator' => ['id' => '1']],
            $amount,
        );
        $this->assertTrue($ok);
    }

    #[Test]
    public function sala_premio_servicio_reparte_la_venta_igual_que_las_demas(): void
    {
        $room = $this->makeRoom('PRIZE');

        $this->sell($room, 100.0);

        $fresh = $room->fresh();
        $this->assertEqualsWithDelta(5.0, (float) $fresh->rke_commission, 0.01);
        $this->assertEqualsWithDelta(10.0, (float) $fresh->room_commission, 0.01);
        $this->assertEqualsWithDelta(85.0, (float) $fresh->prize_pool, 0.01);
        // La venta queda registrada para el creador (antes, excluir PRIZE la perdía).
        $this->assertTrue(BingoCreatorSale::where('room_id', $room->id)->exists());
    }

    #[Test]
    public function el_jackpot_se_descuenta_del_bote_tambien_por_moneda(): void
    {
        $room = $this->makeRoom('ACCUMULATIVE', jackpotPct: 5);

        $this->sell($room, 100.0);

        $fresh = $room->fresh();
        // 100 - 5 (RKE) - 10 (creador) - 5 (jackpot) = 80, en USD y por moneda.
        $this->assertEqualsWithDelta(80.0, (float) $fresh->prize_pool, 0.01);

        $byCurrency = (array) $fresh->metrics_by_currency[$this->currencyId];
        $this->assertEqualsWithDelta(80.0, (float) $byCurrency['prize_pool'], 0.01);
        $this->assertEqualsWithDelta(5.0, (float) $byCurrency['jackpot_contribution'], 0.01);
    }
}
