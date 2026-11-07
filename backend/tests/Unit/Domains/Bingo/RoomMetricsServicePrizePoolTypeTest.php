<?php

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoLotSaleReversalService;
use App\Domains\Games\Bingo\Services\Room\Metrics\RoomMetricsService;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomStateCoordinator;
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
    private string $currencyId = '930000000000000001';
    private string $previousSqlMode = '';

    protected function setUp() : void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->previousSqlMode = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
        DB::statement("SET SESSION sql_mode = ''");
        // PlatformWalletService/JackpotLedgerService son final (no mockeables); el
        // servicio ya los envuelve en try/catch y DatabaseTransactions revierte lo que escriban.
    }

    protected function tearDown() : void
    {
        DB::statement('SET SESSION sql_mode = ?', [$this->previousSqlMode]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    #[Test]
    public function el_jackpot_se_descuenta_del_bote_tambien_por_moneda() : void
    {
        $room = $this->makeRoom('ACCUMULATIVE', jackpotPct: 5);

        $this->sell($room, 100.0);

        $fresh = $room->fresh();
        // 100 - 5 (RKE) - 10 (creador) - 5 (jackpot) = 80, en USD y por moneda.
        $this->assertEqualsWithDelta(80.0, (float) $fresh->prize_pool, 0.01);

        $byCurrency = (array) $fresh->metrics_by_currency[$this->currencyId];
        $this->assertEqualsWithDelta(80.0, (float) $byCurrency['prize_pool'], 0.01);
        $this->assertEqualsWithDelta(5.0, (float) $byCurrency['jackpot_contribution'], 0.01);

        // Y en BingoCreatorSale el jackpot va en su propia columna, no mezclado con premios.
        $sale = BingoCreatorSale::where('room_id', $room->id)->firstOrFail();
        $this->assertEqualsWithDelta(5.0, (float) $sale->jackpot_contribution, 0.01);
        $this->assertEqualsWithDelta(80.0, (float) $sale->prize_pool_amount, 0.01);
        $this->assertTrue($sale->is_valid, 'venta = RKE + creador + vendedor + jackpot + premios');
    }

    #[Test]
    public function lote_sin_desglose_no_se_revierte_y_lo_informa() : void
    {
        $room = $this->makeRoom('ACCUMULATIVE');
        $this->sell($room->fresh(), 100.0);

        $lot = new BingoCardLot(['financial_breakdown' => null]);
        $lot->id = '930000000000000778';

        $this->assertFalse(app(BingoLotSaleReversalService::class)->reverse($lot, $room->fresh())['reversed']);
        $this->assertEqualsWithDelta(100.0, (float) $room->fresh()->total_sales, 0.01);
    }

    #[Test]
    public function premio_servicio_hibrido_acumula_bote_para_sus_rondas_en_porcentaje() : void
    {
        $room = $this->makeRoom('PRIZE', roundTypes: ['OTHER', 'PCT']);

        $this->sell($room, 100.0);

        $this->assertEqualsWithDelta(85.0, (float) $room->fresh()->prize_pool, 0.01);
    }

    #[Test]
    public function premio_servicio_puro_no_acumula_bote_y_registra_los_fondos_del_premio() : void
    {
        $room = $this->makeRoom('PRIZE', roundTypes: ['OTHER', 'OTHER']);

        $this->sell($room, 100.0);

        $fresh = $room->fresh();
        // Comisiones igual que cualquier sala…
        $this->assertEqualsWithDelta(5.0, (float) $fresh->rke_commission, 0.01);
        $this->assertEqualsWithDelta(10.0, (float) $fresh->room_commission, 0.01);
        // …pero sin bote en dinero: ninguna ronda lo consumiría.
        $this->assertEqualsWithDelta(0.0, (float) $fresh->prize_pool, 0.01);

        $byCurrency = (array) $fresh->metrics_by_currency[$this->currencyId];
        $this->assertEqualsWithDelta(0.0, (float) $byCurrency['prize_pool'], 0.01);
        $this->assertEqualsWithDelta(85.0, (float) $byCurrency['prize_funding'], 0.01);

        // El neto queda registrado para el creador como fondos del premio físico.
        $sale = BingoCreatorSale::where('room_id', $room->id)->firstOrFail();
        $this->assertEqualsWithDelta(85.0, (float) $sale->prize_pool_amount, 0.01);
        $this->assertTrue($sale->is_valid);
    }

    #[Test]
    public function reembolsar_un_lote_revierte_exactamente_su_venta() : void
    {
        $room = $this->makeRoom('ACCUMULATIVE', jackpotPct: 5);
        $jackpotTypeId = '930000000000000999';

        $keptBreakdown = $this->sell($room->fresh(), 40.0);
        $refundBreakdown = $this->sell($room->fresh(), 100.0);

        $keptLot = BingoCardLot::create([
            'room_id' => $room->id,
            'user_id' => '1',
            'room_user_id' => '930000000000000701',
            'currency_id' => $this->currencyId,
            'card_count' => 1,
            'card_price' => 40,
            'exchange_rate' => 1,
            'sale_amount' => 40,
            'status' => 'ACTIVE',
            'financial_breakdown' => $keptBreakdown,
        ]);
        $lot = BingoCardLot::create([
            'room_id' => $room->id,
            'user_id' => '1',
            'room_user_id' => '930000000000000702',
            'currency_id' => $this->currencyId,
            'card_count' => 1,
            'card_price' => 100,
            'exchange_rate' => 1,
            'sale_amount' => 100,
            'status' => 'ACTIVE',
            'financial_breakdown' => $refundBreakdown,
        ]);
        app(BingoRoomStateCoordinator::class)->synchronizeCounters((string) $room->id);

        $jackpotBefore = (float) DB::table('jackpots')->where('jackpot_type_id', $jackpotTypeId)->value('amount');
        $this->assertTrue(app(BingoLotSaleReversalService::class)->reverse($lot, $room->fresh())['reversed']);
        $lot->update(['status' => 'REFUNDED']);
        app(BingoRoomStateCoordinator::class)->synchronizeCounters((string) $room->id);

        // La sala queda como si solo existiera la venta de 40.
        $fresh = $room->fresh();
        $this->assertEqualsWithDelta(40.0, (float) $fresh->total_sales, 0.01);
        $this->assertEquals(1, (int) $fresh->total_cards);
        $this->assertEqualsWithDelta(32.0, (float) $fresh->prize_pool, 0.01); // 40 - 5% - 10% - 5%
        $this->assertEqualsWithDelta(2.0, (float) $fresh->rke_commission, 0.01);
        $byCurrency = (array) $fresh->metrics_by_currency[$this->currencyId];
        $this->assertEqualsWithDelta(32.0, (float) $byCurrency['prize_pool'], 0.01);
        $this->assertEqualsWithDelta(2.0, (float) $byCurrency['jackpot_contribution'], 0.01);

        $sale = BingoCreatorSale::where('room_id', $room->id)->firstOrFail();
        $this->assertEqualsWithDelta(40.0, (float) $sale->sale_amount, 0.01);
        $this->assertEqualsWithDelta(2.0, (float) $sale->jackpot_contribution, 0.01);

        // El jackpot devuelve el aporte de la venta reembolsada (5 USD).
        $jackpotAfter = (float) DB::table('jackpots')->where('jackpot_type_id', $jackpotTypeId)->value('amount');
        $this->assertEqualsWithDelta(5.0, $jackpotBefore - $jackpotAfter, 0.01);
        $this->assertTrue(DB::table('jackpot_movements')->where('idempotency_key', 'bingo-lot-jackpot-reversal-' . $lot->id)->exists());

        // Idempotente: una segunda reversa del mismo lote no vuelve a tocar el jackpot.
        app(\App\Services\Jackpot\JackpotLedgerService::class)->reverseContribution($jackpotTypeId, 5.0, 'bingo-lot-jackpot-reversal-' . $lot->id);
        $this->assertEqualsWithDelta($jackpotAfter, (float) DB::table('jackpots')->where('jackpot_type_id', $jackpotTypeId)->value('amount'), 0.01);
    }

    /** @param string[] $roundTypes tipos canónicos de bingo_rounds.prize_type (PCT/FIXED/OTHER) */
    private function makeRoom(string $poolCode, ?float $jackpotPct = null, array $roundTypes = ['PCT']) : BingoRoom
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
        foreach ($roundTypes as $i => $type) {
            DB::table('bingo_rounds')->insert([
                'id' => (string) ((int) $roomId + 2000 + $i), 'room_id' => $roomId,
                'round' => $i + 1, 'prize_type' => $type, 'status' => 'ACTIVE',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return BingoRoom::find($roomId);
    }

    private function sell(BingoRoom $room, float $amount) : ?array
    {
        $breakdown = null;
        $ok = app(RoomMetricsService::class)->updateRoomMetricsWithCommissions(
            $room,
            ['cardCount' => 1, 'currency' => $this->currencyId, 'creator' => ['id' => '1']],
            $amount,
            0,
            $breakdown,
        );
        $this->assertTrue($ok);

        return $breakdown;
    }
}
