<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoLotSaleReversalService;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomCreatorPayoutService;
use App\Domains\Games\Bingo\Services\Room\Metrics\RoomMetricsService;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Models\Platform\FinancialReconciliationItem;
use App\Models\Platform\PlatformReceivable;
use App\Services\Platform\PlatformReceivableService;
use App\Services\Platform\PlatformWalletService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Liquidación del dinero de Bingo según el canal de venta:
 *  - billetera: RKE a la plataforma al vender; comisión del creador al cerrar la sala.
 *  - fuera del sistema: RKE como deuda del comercio (se acredita al cobrarla).
 * Y conciliación: lo que no se puede revertir queda como alerta visible.
 */
class BingoFinancialSettlementTest extends TestCase
{
    use DatabaseTransactions;

    private static int $seq = 0;
    private string $previousSqlMode = '';
    private string $currencyId = '950000000000000001';
    private string $creatorId  = '950000000000000002';

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->previousSqlMode = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
        DB::statement("SET SESSION sql_mode = ''");
        Cache::flush();
        \App\Models\Country::firstOrCreate(['iso_code_2' => 'VE'], ['name' => 'Venezuela', 'iso_code_3' => 'VEN']);
    }

    protected function tearDown(): void
    {
        DB::statement('SET SESSION sql_mode = ?', [$this->previousSqlMode]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    private function makeRoom(): BingoRoom
    {
        $n = ++self::$seq;
        $typeId = (string) (950000000000000100 + $n);
        $roomId = (string) (950000000000000200 + $n);
        DB::table('prize_pool_types')->insert(['id' => $typeId, 'game_id' => '1', 'code' => 'ACCUMULATIVE', 'label' => 'Acumulativo', 'status' => 'ACTIVE']);
        DB::table('bingo_rooms')->insert([
            'id' => $roomId, 'status_id' => '1', 'creator_user_id' => $this->creatorId, 'paid_prizes' => 'N',
            'uuid_code' => 'FIN-' . $roomId, 'prize_pool' => 0, 'total_cards' => 0, 'total_sales' => 0,
            'rke_commission' => 0, 'room_commission' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('bingo_rooms_config')->insert([
            'id' => (string) ((int) $roomId + 1000), 'room_id' => $roomId, 'prize_pool_type_id' => $typeId,
            'rke_commission' => 5, 'room_commission' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return BingoRoom::find($roomId);
    }

    /** Vende y crea el lote con su desglose (como BingoCardGenerationService). */
    private function sellLot(BingoRoom $room, float $amount, string $channel, array $networkCommission = []): BingoCardLot
    {
        $breakdown = null;
        $this->assertTrue(app(RoomMetricsService::class)->updateRoomMetricsWithCommissions(
            $room->fresh(),
            ['cardCount' => 1, 'currency' => $this->currencyId, 'creator' => ['id' => $this->creatorId], 'saleChannel' => $channel,
             'networkCommission' => $networkCommission],
            $amount, 0, $breakdown,
        ));

        $lot = BingoCardLot::create([
            'room_id' => $room->id, 'user_id' => $this->creatorId, 'room_user_id' => '1', 'currency_id' => $this->currencyId,
            'card_count' => 1, 'card_price' => $amount, 'sale_amount' => $amount, 'exchange_rate' => 1,
            'status' => 'ACTIVE', 'financial_breakdown' => $breakdown,
        ]);

        if (is_array($breakdown['receivable_local'] ?? null)) {
            app(PlatformReceivableService::class)->record($this->creatorId, $this->currencyId, $breakdown['receivable_local'], (string) $room->id, (string) $lot->id);
        }
        return $lot;
    }

    private function platformBalance(): float
    {
        return (float) (app(PlatformWalletService::class)->resolveWallet($this->currencyId)?->fresh()->balance ?? 0);
    }

    #[Test]
    public function venta_fuera_del_sistema_no_acredita_rke_y_queda_como_deuda_hasta_cobrarse(): void
    {
        $room = $this->makeRoom();
        $this->sellLot($room, 100.0, 'offline');

        $this->assertEqualsWithDelta(0.0, $this->platformBalance(), 0.001, 'El dinero lo tiene el comercio: nada en la wallet de plataforma');
        $this->assertEquals(1, PlatformReceivable::where('status', 'PENDING')->count());

        // Resumen agrupado por comercio y moneda (panel de Conciliación / dashboard del comercio).
        DB::table('users')->insert(['id' => $this->creatorId, 'username' => 'comercio_fin', 'email' => 'fin@test.local', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('currencies')->insert(['id' => $this->currencyId, 'iso_code' => 'VES', 'symbol' => 'Bs', 'name' => 'Bolívar', 'created_at' => now(), 'updated_at' => now()]);
        $summary = app(PlatformReceivableService::class)->pendingSummary($this->creatorId);
        $this->assertCount(1, $summary);
        $this->assertSame('comercio_fin', $summary[0]['debtor']);
        $this->assertSame('VES', $summary[0]['currency']);
        $this->assertSame(1, $summary[0]['sales']);
        // 100 - 5 (RKE) - 10 (creador) = 85 al bote → el comercio debe RKE + bote = 90.
        $this->assertEqualsWithDelta(90.0, $summary[0]['total'], 0.001);
        $this->assertEqualsWithDelta(5.0, $summary[0]['rke'], 0.001);
        $this->assertEqualsWithDelta(85.0, $summary[0]['prize_pool'], 0.001);

        // El superadmin marca cobrado → recién ahí entra a la wallet de plataforma
        // (RKE como comisión, bote como fondeo de premios).
        $result = app(PlatformReceivableService::class)->settle($this->creatorId, $this->currencyId, '1');
        $this->assertSame(1, $result['count']);
        $this->assertEqualsWithDelta(90.0, $this->platformBalance(), 0.001);
        $this->assertSame('SETTLED', PlatformReceivable::first()->status);
        $this->assertTrue(WalletMovement::where('type', WalletMovement::TYPE_RKE_COMMISSION)->where('amount', 5)->exists());
        $this->assertTrue(WalletMovement::where('type', WalletMovement::TYPE_BINGO_POOL_FUNDING)->where('amount', 85)->exists());
    }

    #[Test]
    public function la_comision_de_agencia_y_comercializadora_no_forma_parte_de_la_deuda(): void
    {
        $room = $this->makeRoom();
        // Agencia 3 + comercializadora 2: se las queda la red, salen del bote.
        $this->sellLot($room, 100.0, 'offline', ['subsidiary' => 3, 'commerce' => 2]);

        $debt = PlatformReceivable::firstOrFail();
        $this->assertEqualsWithDelta(5.0, (float) $debt->rke_amount, 0.001);
        $this->assertEqualsWithDelta(80.0, (float) $debt->prize_pool_amount, 0.001); // 100 - 5 - 10 - 3 - 2
        $this->assertEqualsWithDelta(85.0, (float) $debt->amount, 0.001);
    }

    #[Test]
    public function venta_con_billetera_acredita_rke_al_vender(): void
    {
        $room = $this->makeRoom();
        $this->sellLot($room, 100.0, 'wallet');

        $this->assertEqualsWithDelta(5.0, $this->platformBalance(), 0.001);
        $this->assertEquals(0, PlatformReceivable::count());
    }

    #[Test]
    public function reembolso_de_venta_fuera_del_sistema_anula_la_deuda_pendiente(): void
    {
        $room = $this->makeRoom();
        $lot = $this->sellLot($room, 100.0, 'offline');

        $result = app(BingoLotSaleReversalService::class)->reverse($lot->fresh(), $room->fresh());

        $this->assertSame('CANCELLED', PlatformReceivable::first()->status);
        $this->assertEqualsWithDelta(0.0, $this->platformBalance(), 0.001);
        $this->assertSame(0.0, $result['refund_to_seller'], 'El comercio tiene todo el efectivo: no se le acredita nada');
        $this->assertEquals(0, FinancialReconciliationItem::count());
    }

    #[Test]
    public function reembolso_de_deuda_ya_cobrada_le_devuelve_al_comercio_lo_cobrado(): void
    {
        $room = $this->makeRoom();
        $lot = $this->sellLot($room, 100.0, 'offline');
        app(PlatformReceivableService::class)->settle($this->creatorId, $this->currencyId, '1');
        $this->assertEqualsWithDelta(90.0, $this->platformBalance(), 0.001);

        $result = app(BingoLotSaleReversalService::class)->reverse($lot->fresh(), $room->fresh());

        $this->assertEqualsWithDelta(0.0, $this->platformBalance(), 0.001);
        $this->assertEqualsWithDelta(90.0, $result['refund_to_seller'], 0.001);
    }

    #[Test]
    public function job_de_reembolso_no_acredita_al_comercio_lo_que_ya_tiene_en_efectivo(): void
    {
        $room = $this->makeRoom();
        $wallet = Wallet::create(['user_id' => $this->creatorId, 'currency_id' => $this->currencyId, 'balance' => 0, 'status' => 'ACTIVE']);
        $lot = $this->sellLot($room, 100.0, 'offline'); // pago pendiente / rechazado: deuda sin cobrar

        (new \App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob((string) $lot->id, (string) $room->id))
            ->handle(app(\App\Domains\Wallet\Services\WalletService::class), app(\App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger::class));

        $this->assertSame('REFUNDED', $lot->fresh()->status);
        // Antes se le acreditaba el total (100) aunque el efectivo ya lo tenía — o
        // nunca lo cobró, si el comprobante era falso.
        $this->assertEqualsWithDelta(0.0, (float) $wallet->fresh()->balance, 0.001);
        $this->assertSame('CANCELLED', PlatformReceivable::first()->status);
    }

    #[Test]
    public function si_la_plataforma_ya_retiro_el_rke_queda_una_alerta_de_conciliacion(): void
    {
        $room = $this->makeRoom();
        $lot = $this->sellLot($room, 100.0, 'wallet');
        app(PlatformWalletService::class)->withdraw($this->currencyId, 5.0, 'Retiro de prueba');

        app(BingoLotSaleReversalService::class)->reverse($lot->fresh(), $room->fresh());

        $alert = FinancialReconciliationItem::firstOrFail();
        $this->assertSame(FinancialReconciliationItem::TYPE_RKE_REVERSAL_FAILED, $alert->type);
        $this->assertSame('OPEN', $alert->status);
        $this->assertEqualsWithDelta(5.0, (float) $alert->amount, 0.001);
    }

    #[Test]
    public function al_cerrar_la_sala_se_paga_al_creador_solo_lo_vendido_con_billetera(): void
    {
        $room = $this->makeRoom();
        $wallet = Wallet::create(['user_id' => $this->creatorId, 'currency_id' => $this->currencyId, 'balance' => 0, 'status' => 'ACTIVE']);

        $this->sellLot($room, 100.0, 'wallet');   // comisión 10 → se paga
        $this->sellLot($room, 200.0, 'offline');  // el comercio ya tiene el dinero → no se paga
        $refunded = $this->sellLot($room, 50.0, 'wallet');
        $refunded->update(['status' => 'REFUNDED']); // reembolsado → no cuenta

        $paid = app(BingoRoomCreatorPayoutService::class)->payoutOnClose($room->fresh());

        $this->assertEqualsWithDelta(10.0, $paid[$this->currencyId], 0.001);
        $this->assertEqualsWithDelta(10.0, (float) $wallet->fresh()->balance, 0.001);
        $this->assertTrue(WalletMovement::where('type', WalletMovement::TYPE_CREATOR_COMMISSION)->where('wallet_id', $wallet->id)->exists());

        // Idempotente: reintentar el cierre no paga dos veces.
        app(BingoRoomCreatorPayoutService::class)->payoutOnClose($room->fresh());
        $this->assertEqualsWithDelta(10.0, (float) $wallet->fresh()->balance, 0.001);
    }

    #[Test]
    public function si_el_creador_no_tiene_billetera_queda_alerta_de_pago_fallido(): void
    {
        $room = $this->makeRoom();
        $this->sellLot($room, 100.0, 'wallet');

        $paid = app(BingoRoomCreatorPayoutService::class)->payoutOnClose($room->fresh());

        $this->assertSame([], $paid);
        $this->assertSame(FinancialReconciliationItem::TYPE_CREATOR_PAYOUT_FAILED, FinancialReconciliationItem::firstOrFail()->type);
    }
}
