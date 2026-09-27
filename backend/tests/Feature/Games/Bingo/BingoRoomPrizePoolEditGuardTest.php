<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\DTOs\Room\BingoRoomUpdateDTO;
use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Services\Room\BingoRoomCrudService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * BingoRoomCrudService::guardPrizePoolUnchanged(): con cartones vendidos no
 * se puede cambiar el tipo de bote ni el "Valor Fijo/Extra" al editar, porque
 * prize_pool ya incluye los aportes de esas ventas y no se re-siembra.
 */
class BingoRoomPrizePoolEditGuardTest extends TestCase
{
    use DatabaseTransactions;

    private string $accumulativeId = '900000000000000011';
    private string $fixedId        = '900000000000000012';

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('prize_pool_types')->insert([
            ['id' => $this->accumulativeId, 'game_id' => '1', 'code' => 'ACCUMULATIVE', 'label' => 'Bote Acumulativo', 'status' => 'ACTIVE'],
            ['id' => $this->fixedId,        'game_id' => '1', 'code' => 'FIXED',        'label' => 'Bote Fijo',        'status' => 'ACTIVE'],
        ]);
    }

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    private function roomWith(string $typeId, ?float $amount): BingoRoom
    {
        $room = new BingoRoom();
        $room->setRelation('config', new BingoRoomConfig([
            'prize_pool_type_id'      => $typeId,
            'amount_added_prize_pool' => $amount,
        ]));
        return $room;
    }

    private function guard(BingoRoom $room, string $typeId, ?float $amount): void
    {
        $dto = BingoRoomUpdateDTO::fromArray(['prizePoolType' => $typeId, 'amountAddedPrizePool' => $amount]);
        $method = new \ReflectionMethod(BingoRoomCrudService::class, 'guardPrizePoolUnchanged');
        $method->invoke(app(BingoRoomCrudService::class), $room, $dto);
    }

    #[Test]
    public function mismo_tipo_y_monto_pasa(): void
    {
        $this->guard($this->roomWith($this->fixedId, 100.0), $this->fixedId, 100.0);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function cambiar_el_tipo_de_bote_se_rechaza(): void
    {
        $this->expectException(BingoRoomException::class);
        $this->guard($this->roomWith($this->fixedId, 100.0), $this->accumulativeId, null);
    }

    #[Test]
    public function cambiar_el_monto_de_un_bote_fijo_se_rechaza(): void
    {
        $this->expectException(BingoRoomException::class);
        $this->guard($this->roomWith($this->fixedId, 100.0), $this->fixedId, 200.0);
    }

    #[Test]
    public function acumulativo_con_monto_residual_guardado_no_bloquea_la_edicion(): void
    {
        // Sala vieja: bote acumulativo con monto residual guardado; el request
        // ahora descarta el monto (null) — no debe contarse como "cambio".
        $this->guard($this->roomWith($this->accumulativeId, 150.0), $this->accumulativeId, null);
        $this->addToAssertionCount(1);
    }
}
