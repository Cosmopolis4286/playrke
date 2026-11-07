<?php

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomMetricsSynchronizer;
use App\Domains\Games\Bingo\Services\Room\Metrics\RoomMetricsService;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Games\Common\ConnectionType\ConnectionType;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Room\RoomType;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cubre el hallazgo crítico de la auditoría de Bingo (2026-08-31): antes del
 * fix, updateRoomMetricsWithCommissions() calculaba los nuevos totales a
 * partir de los atributos del objeto $room recibido por parámetro, sin
 * releerlos con lockForUpdate(). Dos compras concurrentes en la misma sala
 * -- cada una con su propia instancia de $room cargada ANTES de que la otra
 * hiciera commit -- perdían silenciosamente el incremento financiero de una
 * de ellas (lost update): el jugador paga bien (su wallet tiene lock propio
 * en WalletService), pero total_cards/total_sales/prize_pool/comisiones de
 * la sala quedan subestimados.
 *
 * No es un test de concurrencia real (no hay dos procesos/hilos ni dos
 * conexiones DB paralelas) -- reproduce la condición exacta que causaba el
 * bug: dos llamadas al servicio, cada una con una instancia de BingoRoom
 * distinta que aún ve los contadores en su valor ANTERIOR al primer update.
 * Sin el lockForUpdate() + relectura dentro del método, la segunda llamada
 * sobreescribiría con su propio cálculo parcial en vez de sumar sobre lo que
 * la primera ya persistió.
 */
class RoomMetricsServiceConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private int|string $currencyId;

    private int|string $userId;

    /** @test */
    public function two_concurrent_purchases_do_not_lose_either_increment()
    {
        $room = $this->makeRoom();
        $service = app(RoomMetricsService::class);

        // Dos instancias independientes de la MISMA fila, cargadas ANTES de que
        // cualquiera de las dos compras haga su update -- exactamente lo que
        // ocurriría con dos requests HTTP concurrentes, cada uno resolviendo su
        // propio $room al inicio de BingoCardGenerationService::generateCards().
        $roomSnapshotA = BingoRoom::find($room->id);
        $roomSnapshotB = BingoRoom::find($room->id);

        $saleDataA = ['cardCount' => 2, 'currency' => $this->currencyId];
        $saleDataB = ['cardCount' => 3, 'currency' => $this->currencyId];

        // Compra A: 2 cartones x $10 = $20
        $resultA = $service->updateRoomMetricsWithCommissions($roomSnapshotA, $saleDataA, 20.0);
        $this->assertTrue($resultA);

        // Compra B: 3 cartones x $10 = $30 -- su $roomSnapshotB todavía tiene
        // total_cards=0/total_sales=0 en memoria, igual que antes de la compra A.
        $resultB = $service->updateRoomMetricsWithCommissions($roomSnapshotB, $saleDataB, 30.0);
        $this->assertTrue($resultB);

        $fresh = $room->fresh();

        // Sin el fix: total_cards/total_sales de B pisarían a los de A (quedarían
        // en 3/30 en vez de 2+3=5 / 20+30=50). Con el fix (relectura con
        // lockForUpdate() dentro del método), ambos incrementos se acumulan.
        $this->assertEquals(5, $fresh->total_cards, 'total_cards debe acumular ambas compras, no perder la primera.');
        $this->assertEquals(50.0, (float) $fresh->total_sales, 'total_sales debe acumular ambas compras, no perder la primera.');

        // rke_commission (5%) y room_commission (10%) también deben acumularse
        // sobre el total real ($50), no solo sobre la última venta procesada ($30).
        $this->assertEqualsWithDelta(2.5, (float) $fresh->rke_commission, 0.01, 'rke_commission debe acumular ambas ventas.');
        $this->assertEqualsWithDelta(5.0, (float) $fresh->room_commission, 0.01, 'room_commission debe acumular ambas ventas.');
    }


    /** @test */
    public function synchronizer_repairs_room_counters_and_financial_totals_from_active_lots()
    {
        $room = $this->makeRoom();
        $room->update([
            'total_players' => 77,
            'total_cards' => 99,
            'total_sales' => 999,
            'rke_commission' => 99,
            'room_commission' => 99,
            'prize_pool' => 999,
        ]);

        $roomUser = BingoRoomUser::create([
            'room_id' => $room->id,
            'user_id' => $this->userId,
            'role' => BingoRoomUser::ROLE_PLAYER,
            'cards_purchased' => 2,
        ]);

        BingoCardLot::create([
            'room_id' => $room->id,
            'user_id' => $this->userId,
            'room_user_id' => $roomUser->id,
            'currency_id' => $this->currencyId,
            'card_count' => 2,
            'card_price' => 5,
            'exchange_rate' => 1,
            'sale_amount' => 10,
            'status' => 'ACTIVE',
            'financial_breakdown' => [
                'version' => 1,
                'channel' => 'wallet',
                'currency_id' => (string) $this->currencyId,
                'room_usd' => [
                    'total_sales' => 10,
                    'total_cards' => 2,
                    'rke_commission' => 0.5,
                    'room_commission' => 1,
                    'seller_commission' => 0,
                    'subsidiary_commission' => 0,
                    'commerce_commission' => 0,
                    'prize_pool' => 8.5,
                ],
                'room_local' => [
                    'total_sales' => 10,
                    'total_cards' => 2,
                    'prize_pool' => 8.5,
                    'full_house_prize' => 8.5,
                    'rke_commission' => 0.5,
                    'room_commission' => 1,
                    'seller_commission' => 0,
                    'jackpot_contribution' => 0,
                ],
            ],
        ]);

        $result = app(BingoRoomMetricsSynchronizer::class)->synchronize($room);
        $fresh = $room->fresh();

        $this->assertSame(1, $fresh->total_players);
        $this->assertSame(2, $fresh->total_cards);
        $this->assertEquals(10.0, (float) $fresh->total_sales);
        $this->assertEquals(0.5, (float) $fresh->rke_commission);
        $this->assertEquals(1.0, (float) $fresh->room_commission);
        $this->assertEquals(8.5, (float) $fresh->prize_pool);
        $this->assertTrue($result['financials_reconciled']);
    }

    /** @test */
    public function update_all_metrics_rolls_back_prior_increments_when_a_later_metric_fails()
    {
        $room = $this->makeRoom();
        $service = new class extends RoomMetricsService {
            public function updateTotalSales(BingoRoom $room, float $saleAmount) : bool
            {
                return false;
            }
        };

        $result = $service->updateAllMetrics($room, [
            'cardCount' => 2,
            'saleAmount' => 20.0,
        ]);

        $this->assertFalse($result);
        $this->assertSame(0, (int) $room->fresh()->total_cards);
        $this->assertSame(0.0, (float) $room->fresh()->total_sales);
    }

    private function makeRoom() : BingoRoom
    {
        $country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO'],
        );

        $user = User::factory()->create(['country_id' => $country->id]);
        $this->userId = $user->id;

        $status = GameStatus::firstOrCreate(['code' => 'ACTIVE'], ['label' => 'Activa']);
        $roomType = RoomType::firstOrCreate(['code' => 'PUBLIC'], ['label' => 'Pública']);
        $connectionType = ConnectionType::firstOrCreate(['code' => 'ONLINE'], ['label' => 'Online']);
        // El tipo de bote no altera el reparto de la venta (ver RoomMetricsService:
        // todos los tipos, incluido PRIZE, acumulan comisiones y bote).
        // game_id es NOT NULL (prize_pool_types es per-game, ver PrizePoolTypeSeeder) —
        // sin este Game::firstOrCreate() previo, el insert falla con
        // "Field 'game_id' doesn't have a default value".
        $game = \App\Models\Game::firstOrCreate(['name' => 'BINGO'], ['status' => 'ACTIVE']);
        $prizePoolType = PrizePoolType::firstOrCreate(
            ['game_id' => $game->id, 'code' => 'STANDARD'],
            ['label' => 'Estándar', 'is_accumulative' => false],
        );
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        $room = BingoRoom::create([
            'uuid_code' => (string) Str::uuid(),
            'status_id' => $status->id,
            'creator_user_id' => $user->id,
            'total_cards' => 0,
            'total_sales' => 0,
            'prize_pool' => 0,
            'rke_commission' => 0,
            'room_commission' => 0,
        ]);

        BingoRoomConfig::create([
            'room_id' => $room->id,
            'room_type_id' => $roomType->id,
            'creator_user_id' => $user->id,
            'connection_type_id' => $connectionType->id,
            'prize_pool_type_id' => $prizePoolType->id,
            'rke_commission' => 5,
            'room_commission' => 10,
        ]);

        $this->currencyId = $currency->id;

        return $room;
    }
}
