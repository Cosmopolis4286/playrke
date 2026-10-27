<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\DTOs\Room\BingoRoomUpdateDTO;
use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Card\CardPrice\BingoCardPrice;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\Models\Round\BingoRoundPrize;
use App\Domains\Games\Bingo\Services\Room\BingoRoomCrudService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * BingoRoomCrudService::guardSalesLockedFields(): con cartones vendidos no se
 * puede cambiar lo que el jugador compró — bote, rondas y premios, %Rondas,
 * seriales, variante y precio del cartón. El resto sigue editable.
 *
 * La sala se arma en memoria (relaciones con setRelation) para no depender de
 * las muchas columnas NOT NULL de las tablas de sala; solo prize_pool_types va a BD.
 */
class BingoRoomPrizePoolEditGuardTest extends TestCase
{
    use DatabaseTransactions;

    private string $accumulativeId = '900000000000000011';
    private string $fixedId = '900000000000000012';
    private string $usd = '900000000000000021';
    private string $ves = '900000000000000022';

    protected function setUp() : void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('prize_pool_types')->insert([
            ['id' => $this->accumulativeId, 'game_id' => '1', 'code' => 'ACCUMULATIVE', 'label' => 'Bote Acumulativo', 'status' => 'ACTIVE'],
            ['id' => $this->fixedId,        'game_id' => '1', 'code' => 'FIXED',        'label' => 'Bote Fijo',        'status' => 'ACTIVE'],
        ]);
    }

    protected function tearDown() : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    #[Test]
    public function acumulativo_con_monto_residual_guardado_no_bloquea_la_edicion() : void
    {
        $room = $this->room(['prize_pool_type_id' => $this->accumulativeId, 'amount_added_prize_pool' => 150.0]);
        $this->guard($room, $this->dto(['prizePoolType' => $this->accumulativeId, 'amountAddedPrizePool' => null]));
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function agregar_una_moneda_nueva_con_su_precio_se_permite() : void
    {
        $this->guard($this->room(), $this->dto(['cardPriceByCurrency' => [$this->usd => 1.5, $this->ves => 60]]));
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function cambiar_el_porcentaje_de_una_ronda_se_rechaza() : void
    {
        $this->expectException(BingoRoomException::class);
        $this->guard($this->room(), $this->dto(['roundsConfig' => [
            ['prizeType' => 'percentage', 'bingoPattern' => '501', 'prizeValue' => 50],
            ['prizeType' => 'prize', 'bingoPattern' => '502', 'prizeId' => '777'],
        ]]));
    }

    #[Test]
    public function cambiar_el_premio_fisico_de_una_ronda_se_rechaza() : void
    {
        $this->expectException(BingoRoomException::class);
        $this->guard($this->room(), $this->dto(['roundsConfig' => [
            ['prizeType' => 'percentage', 'bingoPattern' => '501', 'prizeValue' => 60],
            ['prizeType' => 'prize', 'bingoPattern' => '502', 'prizeId' => '888'],
        ]]));
    }

    #[Test]
    public function cambiar_el_tipo_de_bote_se_rechaza() : void
    {
        $this->expectException(BingoRoomException::class);
        $this->guard($this->room(), $this->dto(['prizePoolType' => $this->accumulativeId]));
    }

    #[Test]
    public function cambiar_el_valor_fijo_extra_se_rechaza() : void
    {
        $this->expectException(BingoRoomException::class);
        $this->guard($this->room(), $this->dto(['amountAddedPrizePool' => 200]));
    }

    #[Test]
    public function cambiar_la_cantidad_de_rondas_se_rechaza() : void
    {
        $this->expectException(BingoRoomException::class);
        $this->guard($this->room(), $this->dto(['rounds' => 3]));
    }

    #[Test]
    public function cambiar_seriales_variante_o_precio_se_rechaza() : void
    {
        foreach ([
            ['numSerials' => 20],
            ['gameVariant' => 'BINGO_90'],
            ['cardPriceByCurrency' => [$this->usd => 2]],
            ['useBaseUsd' => true, 'cardPriceUsd' => 1.5],
        ] as $override) {
            try {
                $this->guard($this->room(), $this->dto($override));
                $this->fail('Se esperaba bloqueo por ventas para ' . json_encode($override));
            } catch (BingoRoomException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function reenviar_el_formulario_sin_cambios_pasa() : void
    {
        // Incluye cambios en campos editables (comisión) y datos que solo son de
        // presentación (prizeName, prizeValue del premio físico).
        $this->guard($this->room(), $this->dto());
        $this->addToAssertionCount(1);
    }

    /** DTO equivalente a reenviar el formulario sin cambios, con overrides. */
    private function dto(array $overrides = []) : BingoRoomUpdateDTO
    {
        return BingoRoomUpdateDTO::fromArray(array_merge([
            'prizePoolType' => $this->fixedId,
            'amountAddedPrizePool' => 100,
            'rounds' => 2,
            'roundsPct' => 60,
            'useSerials' => true,
            'serialsPct' => 5,
            'numSerials' => 10,
            'gameVariant' => 'BINGO_75',
            'useBaseUsd' => false,
            'cardPriceByCurrency' => [$this->usd => 1.5],
            'roomCommn' => 15,
            'roundsConfig' => [
                ['prizeType' => 'percentage', 'bingoPattern' => '501', 'prizeValue' => 60, 'prizeName' => null],
                ['prizeType' => 'prize', 'bingoPattern' => '502', 'prizeId' => '777', 'prizeName' => 'TV', 'prizeValue' => 300],
            ],
        ], $overrides));
    }

    private function guard(BingoRoom $room, BingoRoomUpdateDTO $dto) : void
    {
        (new ReflectionMethod(BingoRoomCrudService::class, 'guardSalesLockedFields'))
            ->invoke(app(BingoRoomCrudService::class), $room, $dto);
    }

    /** Sala FIXED con 2 rondas (60% / premio físico), seriales y precio manual por moneda. */
    private function room(array $config = []) : BingoRoom
    {
        $room = new BingoRoom;
        $room->setRelation('config', new BingoRoomConfig(array_merge([
            'prize_pool_type_id' => $this->fixedId,
            'amount_added_prize_pool' => 100.0,
            'rounds' => 2,
            'rounds_pct' => 60.0,
            'use_serials' => true,
            'serials_pct' => 5.0,
            'num_serials' => 10,
            'game_variant' => 'BINGO_75',
            'use_base_usd' => false,
        ], $config)));

        $r1 = new BingoRound(['round' => 1, 'prize_type' => 'PCT', 'bingo_pattern_id' => '501']);
        $r1->setRelation('prizes', collect([new BingoRoundPrize(['currency_id' => null, 'prize_id' => null, 'prize_value' => 60])]));
        $r2 = new BingoRound(['round' => 2, 'prize_type' => 'OTHER', 'bingo_pattern_id' => '502']);
        $r2->setRelation('prizes', collect([new BingoRoundPrize(['currency_id' => null, 'prize_id' => '777', 'prize_value' => null])]));
        $room->setRelation('rounds', collect([$r1, $r2]));

        $room->setRelation('cardPrices', collect([
            new BingoCardPrice(['currency_id' => $this->usd, 'card_price' => 1.5]),
        ]));

        return $room;
    }
}
