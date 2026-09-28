<?php

namespace Tests\Unit\Services\Platform;

use App\Domains\Wallet\Models\WalletMovement;
use App\Services\Platform\PlatformWalletService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PlatformWalletService::reverseRke(): revierte el RKE de una venta
 * reembolsada — idempotente y sin dejar la wallet en negativo.
 */
class PlatformWalletReverseRkeTest extends TestCase
{
    use DatabaseTransactions;

    private string $currencyId = '940000000000000001';

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Cache::flush();
        // El usuario de plataforma se crea solo (resolvePlatformUser) y necesita país VE.
        \App\Models\Country::firstOrCreate(['iso_code_2' => 'VE'], ['name' => 'Venezuela', 'iso_code_3' => 'VEN']);
    }

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    private function balance(PlatformWalletService $svc): float
    {
        return (float) $svc->resolveWallet($this->currencyId)->fresh()->balance;
    }

    #[Test]
    public function revierte_idempotente_y_sin_saldo_negativo(): void
    {
        $svc = app(PlatformWalletService::class);
        $svc->creditRke($this->currencyId, 10.0, 'Comisión RKE – test');
        $this->assertEqualsWithDelta(10.0, $this->balance($svc), 0.001);

        $movement = $svc->reverseRke($this->currencyId, 4.0, 'test-reversal-1');
        $this->assertNotNull($movement);
        $this->assertSame(WalletMovement::TYPE_RKE_COMMISSION_REVERSAL, $movement->type);
        $this->assertEqualsWithDelta(6.0, $this->balance($svc), 0.001);

        // Mismo idempotency key: no descuenta otra vez.
        $svc->reverseRke($this->currencyId, 4.0, 'test-reversal-1');
        $this->assertEqualsWithDelta(6.0, $this->balance($svc), 0.001);

        // Más de lo disponible: no revierte (se loguea para conciliación) y no queda en negativo.
        $this->assertNull($svc->reverseRke($this->currencyId, 100.0, 'test-reversal-2'));
        $this->assertEqualsWithDelta(6.0, $this->balance($svc), 0.001);
    }
}
