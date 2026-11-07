<?php

namespace Tests\Feature\Games\Bingo;

use App\Http\Requests\Games\Bingo\Room\Automatic\BingoRoomAutomaticRequest;
use App\Http\Requests\Games\Bingo\Room\BingoRoomRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Reglas cruzadas de "Valor Fijo/Extra" (amountAddedPrizePool) según el tipo
 * de bote, y del par jackpotType/jackpotPct — en ambos requests de sala
 * (normal y automática). Ejecuta el pipeline real del FormRequest
 * (prepareForValidation → rules → withValidator) sin pasar por authorize().
 */
class BingoRoomRequestPrizePoolJackpotTest extends TestCase
{
    use DatabaseTransactions;

    private string $accumulativeId = '900000000000000001';
    private string $fixedId = '900000000000000002';
    private string $prizeId = '900000000000000003';

    public static function requestClasses() : array
    {
        return [
            'sala normal' => [BingoRoomRequest::class],
            'sala automática' => [BingoRoomAutomaticRequest::class],
        ];
    }

    private static function rounds(string ...$types) : array
    {
        return array_map(fn ($t) => ['prizeType' => $t], $types);
    }

    protected function setUp() : void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('prize_pool_types')->insert([
            ['id' => $this->accumulativeId, 'game_id' => '1', 'code' => 'ACCUMULATIVE', 'label' => 'Bote Acumulativo', 'status' => 'ACTIVE'],
            ['id' => $this->fixedId,        'game_id' => '1', 'code' => 'FIXED',        'label' => 'Bote Fijo',        'status' => 'ACTIVE'],
            ['id' => $this->prizeId,        'game_id' => '1', 'code' => 'PRIZE',        'label' => 'Premio/Servicio',  'status' => 'ACTIVE'],
        ]);
    }

    protected function tearDown() : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function bote_acumulativo_descarta_el_valor_residual(string $class) : void
    {
        // Un valor fuera de rango que, sin descartarse, fallaría la regla min:20.
        [$errors, $request] = $this->validate($class, ['prizePoolType' => $this->accumulativeId, 'amountAddedPrizePool' => 5]);

        $this->assertFalse($errors->has('amountAddedPrizePool'));
        $this->assertNull($request->input('amountAddedPrizePool'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function bote_no_acumulativo_con_valor_valido_no_da_error(string $class) : void
    {
        [$errors] = $this->validate($class, ['prizePoolType' => $this->fixedId, 'amountAddedPrizePool' => 50]);

        $this->assertFalse($errors->has('amountAddedPrizePool'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function bote_no_acumulativo_exige_valor_fijo_extra(string $class) : void
    {
        /** @var MessageBag $errors */
        [$errors] = $this->validate($class, ['prizePoolType' => $this->fixedId]);

        $this->assertTrue($errors->has('amountAddedPrizePool'));
        $this->assertStringContainsString('Bote Fijo', $errors->first('amountAddedPrizePool'));
    }

    #[Test]
    public function error_de_monedas_no_saltea_el_resto_de_chequeos_cruzados() : void
    {
        // 2 monedas sin tasa activa → error de monedas con `return` temprano,
        // que antes salteaba la suma de % y los premios fijos por moneda.
        [$errors] = $this->validate(BingoRoomRequest::class, [
            'allowedCurrencies' => ['900000000000000050', '900000000000000051'],
            'roomCommn' => 50,
            'roundsPct' => 60,
            'roundsConfig' => [['prizeType' => 'fixed', 'prizeValueByCurrency' => []]],
        ]);

        $this->assertTrue($errors->has('allowedCurrencies'));
        $this->assertTrue($errors->has('percentageSum'));
        $this->assertTrue($errors->has('roundsConfig.0.prizeValueByCurrency.900000000000000050'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function jackpot_pct_sin_tipo_es_invalido(string $class) : void
    {
        [$errors] = $this->validate($class, ['jackpotPct' => 5]);

        $this->assertTrue($errors->has('jackpotType'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function jackpot_tipo_sin_pct_es_invalido(string $class) : void
    {
        [$errors] = $this->validate($class, ['jackpotType' => '900000000000000099']);

        $this->assertTrue($errors->has('jackpotPct'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function minimos_de_capacidad_son_obligatorios(string $class) : void
    {
        [$errors] = $this->validate($class, []);

        $this->assertTrue($errors->has('minPlayers'));
        $this->assertTrue($errors->has('minCardsToStart'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function premio_servicio_hibrido_exige_valor_fijo_extra(string $class) : void
    {
        [$errors] = $this->validate($class, ['prizePoolType' => $this->prizeId, 'roundsConfig' => self::rounds('prize', 'percentage')]);

        $this->assertFalse($errors->has('prizePoolType'));
        $this->assertTrue($errors->has('amountAddedPrizePool'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function premio_servicio_puro_no_usa_valor_fijo_extra(string $class) : void
    {
        [$errors, $request] = $this->validate($class, [
            'prizePoolType' => $this->prizeId, 'roundsConfig' => self::rounds('prize', 'fixed'), 'amountAddedPrizePool' => 50,
        ]);

        $this->assertFalse($errors->has('prizePoolType'));
        $this->assertFalse($errors->has('amountAddedPrizePool'));
        $this->assertNull($request->input('amountAddedPrizePool'), 'sin rondas % no hay bote en dinero que sembrar');
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function premio_servicio_sin_ronda_de_premio_fisico_es_invalido(string $class) : void
    {
        [$errors] = $this->validate($class, ['prizePoolType' => $this->prizeId, 'roundsConfig' => self::rounds('percentage')]);

        $this->assertTrue($errors->has('prizePoolType'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function sin_jackpot_no_da_error_de_jackpot(string $class) : void
    {
        [$errors] = $this->validate($class, []);

        $this->assertFalse($errors->has('jackpotPct'));
        $this->assertFalse($errors->has('jackpotType'));
    }

    /** v1: una sola ronda por sala (varias rondas mezclan las bolas de todas las rondas). */
    #[Test]
    #[DataProvider('requestClasses')]
    public function solo_se_admite_una_ronda(string $class) : void
    {
        [$errors] = $this->validate($class, ['rounds' => 1, 'roundsConfig' => self::rounds('percentage')]);
        $this->assertFalse($errors->has('rounds'));
        $this->assertFalse($errors->has('roundsConfig'));

        [$errors] = $this->validate($class, ['rounds' => 2, 'roundsConfig' => self::rounds('percentage', 'percentage')]);
        $this->assertTrue($errors->has('rounds'));
        $this->assertSame('Por ahora las salas de bingo admiten una sola ronda.', $errors->first('rounds'));
        $this->assertTrue($errors->has('roundsConfig'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function suma_de_porcentajes_se_valida_con_base_usd(string $class) : void
    {
        // useBaseUsd=true es el modo por defecto — en la sala automática un
        // `return` temprano salteaba la suma de % en este caso.
        [$errors] = $this->validate($class, [
            'useBaseUsd' => true,
            'allowedCurrencies' => ['900000000000000050'],
            'roomCommn' => 50,
            'roundsPct' => 60,
        ]);

        $this->assertTrue($errors->has('percentageSum'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function todas_las_rondas_fisicas_exigen_bote_premio_servicio(string $class) : void
    {
        [$errors] = $this->validate($class, ['prizePoolType' => $this->accumulativeId, 'roundsConfig' => self::rounds('prize', 'prize')]);

        $this->assertTrue($errors->has('prizePoolType'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function un_jugador_solo_se_acepta_en_modo_solitario(string $class) : void
    {
        $soloId = '900000000000000031';
        $multiId = '900000000000000032';
        DB::table('game_modes')->insert([
            ['id' => $soloId,  'game_id' => '1', 'code' => 'SOLO',  'label' => 'Solitario'],
            ['id' => $multiId, 'game_id' => '1', 'code' => 'MULTI', 'label' => 'Múltiple'],
        ]);

        [$errors] = $this->validate($class, ['gameMode' => $multiId, 'minPlayers' => 1, 'minCardsToStart' => 1]);
        $this->assertTrue($errors->has('minPlayers'));

        [$errors] = $this->validate($class, ['gameMode' => $soloId, 'minPlayers' => 1, 'minCardsToStart' => 1]);
        $this->assertFalse($errors->has('minPlayers'));
    }

    /** @param class-string<FormRequest> $class */
    private function validate(string $class, array $data) : array
    {
        $request = $class::create('/bingo-rooms', 'POST', $data);
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));

        // Mismo orden que FormRequest::validateResolved(), salvo authorize().
        (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
        $validator = (new ReflectionMethod(FormRequest::class, 'getValidatorInstance'))->invoke($request);
        $validator->passes();

        return [$validator->errors(), $request];
    }
}
