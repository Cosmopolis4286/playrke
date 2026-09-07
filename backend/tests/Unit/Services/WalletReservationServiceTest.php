<?php

namespace Tests\Unit\Services;

use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletHold;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletReservationService;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WalletReservationService::class);

        (new MovementTypeSeeder())->run();
        MovementType::forgetCatalogCache();
    }

    private function makeWallet(float $balance = 100.0): Wallet
    {
        $user     = User::factory()->create();
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        return Wallet::create([
            'user_id'       => $user->id,
            'currency_id'   => $currency->id,
            'balance'       => $balance,
            'bonus_balance' => 0,
            'status'        => 'ACTIVE',
        ]);
    }

    public function test_hold_creates_active_reservation_and_does_not_touch_balance(): void
    {
        $wallet = $this->makeWallet(100.0);

        $hold = $this->service->hold($wallet, 40.0, description: 'test hold');

        $this->assertSame(WalletHold::STATUS_HELD, $hold->status);
        $this->assertSame(40.0, (float) $hold->amount);
        $this->assertSame(100.0, (float) $wallet->fresh()->balance);
    }

    public function test_hold_throws_when_available_balance_insufficient(): void
    {
        $wallet = $this->makeWallet(30.0);

        $this->expectException(WalletDomainException::class);

        $this->service->hold($wallet, 40.0);
    }

    public function test_capture_debits_wallet_and_marks_hold_captured(): void
    {
        $wallet = $this->makeWallet(100.0);
        $hold   = $this->service->hold($wallet, 40.0);

        $movement = $this->service->capture($hold, WalletMovement::TYPE_TRIVIA_ENTRY);

        $this->assertSame(60.0, (float) $wallet->fresh()->balance);
        $this->assertSame(WalletHold::STATUS_CAPTURED, $hold->fresh()->status);
        // assertEquals (no assertSame): el id puede volver como int o string
        // según cómo PDO tipó la columna bigint al leerla — mismo caso que
        // documenta WalletLimit::resolve() para currency_id.
        $this->assertEquals((string) $movement->id, $hold->fresh()->captured_movement_id);
    }

    // ── Bug de carrera: capture()/release() sin lock sobre el propio hold ──

    public function test_capturing_an_already_captured_hold_throws_and_does_not_double_debit(): void
    {
        $wallet = $this->makeWallet(100.0);
        $hold   = $this->service->hold($wallet, 40.0);

        $this->service->capture($hold, WalletMovement::TYPE_TRIVIA_ENTRY);

        $this->expectException(WalletDomainException::class);

        try {
            // Simula una segunda captura concurrente sobre el mismo hold
            // (doble clic, reintento) — antes del fix, isActive() se leía
            // sin lock y esta segunda llamada podía debitar de nuevo.
            $this->service->capture($hold->fresh(), WalletMovement::TYPE_TRIVIA_ENTRY);
        } finally {
            $this->assertSame(60.0, (float) $wallet->fresh()->balance);
        }
    }

    public function test_release_marks_hold_released_without_movement(): void
    {
        $wallet = $this->makeWallet(100.0);
        $hold   = $this->service->hold($wallet, 40.0);

        $released = $this->service->release($hold);

        $this->assertSame(WalletHold::STATUS_RELEASED, $released->status);
        $this->assertSame(100.0, (float) $wallet->fresh()->balance);
    }

    public function test_releasing_an_already_released_hold_throws(): void
    {
        $wallet = $this->makeWallet(100.0);
        $hold   = $this->service->hold($wallet, 40.0);

        $this->service->release($hold);

        $this->expectException(WalletDomainException::class);

        $this->service->release($hold->fresh());
    }

    public function test_capture_amount_exceeding_hold_throws(): void
    {
        $wallet = $this->makeWallet(100.0);
        $hold   = $this->service->hold($wallet, 40.0);

        $this->expectException(WalletDomainException::class);

        $this->service->capture($hold, WalletMovement::TYPE_TRIVIA_ENTRY, amount: 41.0);
    }
}
