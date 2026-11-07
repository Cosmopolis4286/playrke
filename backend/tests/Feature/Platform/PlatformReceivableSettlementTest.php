<?php

namespace Tests\Feature\Platform;

use App\Exceptions\Domain\Platform\ReceivableSettlementException;
use App\Models\Platform\PlatformReceivable;
use App\Services\Platform\PlatformReceivableService;
use App\Services\Platform\PlatformWalletService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Marcar cobrado" de la conciliación: se cobra exactamente lo que el admin
 * vio (hasta la última deuda mostrada y por el mismo total) y queda la
 * referencia del pago del comercio.
 */
class PlatformReceivableSettlementTest extends TestCase
{
    use DatabaseTransactions;

    private static int $seq = 0;
    private string $debtorId = '';
    private string $currencyId = '960000000000000001';

    protected function setUp() : void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Cache::flush();
        \App\Models\Country::firstOrCreate(['iso_code_2' => 'VE'], ['name' => 'Venezuela', 'iso_code_3' => 'VEN']);
        $this->debtorId = (string) \App\Models\User::factory()->create()->id;
        DB::table('currencies')->insert(['id' => $this->currencyId, 'iso_code' => 'VEX', 'symbol' => 'Bs', 'name' => 'Bolívar prueba', 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function tearDown() : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    #[Test]
    public function solo_cobra_las_deudas_que_el_admin_vio_y_guarda_la_referencia() : void
    {
        $this->receivable(10.0);
        $last = $this->receivable(5.0);
        $summary = $this->summary();
        $this->assertSame((string) $last->id, $summary['up_to_id']);
        $this->assertEqualsWithDelta(15.0, $summary['total'], 0.001);

        // Venta registrada después de abrir el panel.
        $later = $this->receivable(7.0);

        $before = $this->platformBalance();
        $result = app(PlatformReceivableService::class)->settle(
            $this->debtorId, $this->currencyId, '1', $summary['up_to_id'], $summary['total'], 'REF-123', 'Transferencia BDV',
        );

        $this->assertSame(2, $result['count']);
        $this->assertEqualsWithDelta(15.0, $result['amount'], 0.001);
        $this->assertEqualsWithDelta($before + 15.0, $this->platformBalance(), 0.001);

        $settled = PlatformReceivable::where('status', PlatformReceivable::STATUS_SETTLED)->get();
        $this->assertCount(2, $settled);
        $this->assertSame(['REF-123'], $settled->pluck('settlement_reference')->unique()->values()->all());
        $this->assertSame([$result['batch_id']], $settled->pluck('settlement_batch_id')->unique()->values()->all());
        $this->assertSame('Transferencia BDV', $settled->first()->settlement_notes);

        // La venta posterior sigue pendiente, con su propio total.
        $this->assertSame(PlatformReceivable::STATUS_PENDING, $later->fresh()->status);
        $this->assertEqualsWithDelta(7.0, $this->summary()['total'], 0.001);
    }

    #[Test]
    public function si_el_total_cambio_no_cobra_nada() : void
    {
        $this->receivable(10.0);
        $cancelled = $this->receivable(5.0);
        $summary = $this->summary();

        // Se anula una venta entre que el admin abrió el panel y confirmó.
        $cancelled->update(['status' => PlatformReceivable::STATUS_CANCELLED]);

        $before = $this->platformBalance();

        try {
            app(PlatformReceivableService::class)->settle(
                $this->debtorId, $this->currencyId, '1', $summary['up_to_id'], $summary['total'], 'REF-1',
            );
            $this->fail('Debió rechazar el cobro por monto distinto');
        } catch (ReceivableSettlementException $e) {
            $this->assertSame('RECEIVABLE_SETTLEMENT_AMOUNT_MISMATCH', $e->getMessage());
        }

        $this->assertSame(0, PlatformReceivable::where('status', PlatformReceivable::STATUS_SETTLED)->count());
        $this->assertEqualsWithDelta($before, $this->platformBalance(), 0.001);
    }

    #[Test]
    public function sin_corte_ni_monto_sigue_cobrando_todo_lo_pendiente() : void
    {
        $this->receivable(10.0);
        $this->receivable(5.0);

        $result = app(PlatformReceivableService::class)->settle($this->debtorId, $this->currencyId, '1');

        $this->assertSame(2, $result['count']);
        $this->assertSame(0, PlatformReceivable::where('status', PlatformReceivable::STATUS_PENDING)->count());
    }

    private function receivable(float $rke) : PlatformReceivable
    {
        $n = ++self::$seq;

        return app(PlatformReceivableService::class)->record(
            $this->debtorId,
            $this->currencyId,
            ['rke' => $rke],
            null,
            (string) (960000000000000100 + $n),
        );
    }

    /** @return array<string, mixed> */
    private function summary() : array
    {
        return collect(app(PlatformReceivableService::class)->pendingSummary($this->debtorId))
            ->firstWhere('currency_id', $this->currencyId);
    }

    private function platformBalance() : float
    {
        return (float) (app(PlatformWalletService::class)->resolveWallet($this->currencyId)?->fresh()->balance ?? 0);
    }
}
