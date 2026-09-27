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
    private string $fixedId        = '900000000000000002';

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

    public static function requestClasses(): array
    {
        return [
            'sala normal'     => [BingoRoomRequest::class],
            'sala automática' => [BingoRoomAutomaticRequest::class],
        ];
    }

    /** @param class-string<FormRequest> $class */
    private function validate(string $class, array $data): array
    {
        $request = $class::create('/bingo-rooms', 'POST', $data);
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));

        // Mismo orden que FormRequest::validateResolved(), salvo authorize().
        (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
        $validator = (new \ReflectionMethod(FormRequest::class, 'getValidatorInstance'))->invoke($request);
        $validator->passes();

        return [$validator->errors(), $request];
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function bote_no_acumulativo_exige_valor_fijo_extra(string $class): void
    {
        /** @var MessageBag $errors */
        [$errors] = $this->validate($class, ['prizePoolType' => $this->fixedId]);

        $this->assertTrue($errors->has('amountAddedPrizePool'));
        $this->assertStringContainsString('Bote Fijo', $errors->first('amountAddedPrizePool'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function bote_no_acumulativo_con_valor_valido_no_da_error(string $class): void
    {
        [$errors] = $this->validate($class, ['prizePoolType' => $this->fixedId, 'amountAddedPrizePool' => 50]);

        $this->assertFalse($errors->has('amountAddedPrizePool'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function bote_acumulativo_descarta_el_valor_residual(string $class): void
    {
        // Un valor fuera de rango que, sin descartarse, fallaría la regla min:20.
        [$errors, $request] = $this->validate($class, ['prizePoolType' => $this->accumulativeId, 'amountAddedPrizePool' => 5]);

        $this->assertFalse($errors->has('amountAddedPrizePool'));
        $this->assertNull($request->input('amountAddedPrizePool'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function jackpot_pct_sin_tipo_es_invalido(string $class): void
    {
        [$errors] = $this->validate($class, ['jackpotPct' => 5]);

        $this->assertTrue($errors->has('jackpotType'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function jackpot_tipo_sin_pct_es_invalido(string $class): void
    {
        [$errors] = $this->validate($class, ['jackpotType' => '900000000000000099']);

        $this->assertTrue($errors->has('jackpotPct'));
    }

    #[Test]
    #[DataProvider('requestClasses')]
    public function sin_jackpot_no_da_error_de_jackpot(string $class): void
    {
        [$errors] = $this->validate($class, []);

        $this->assertFalse($errors->has('jackpotPct'));
        $this->assertFalse($errors->has('jackpotType'));
    }
}
