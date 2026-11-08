<?php

namespace Tests\Feature\Games\Trivia;

use App\Domains\Games\Trivia\Actions\StartGameAction;
use App\Domains\Games\Trivia\Exceptions\Game\TriviaGameException;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletService;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * T5: sin preguntas suficientes en el banco la partida terminaba vacía (o
 * corta) y se quedaba con la entrada. Ahora no inicia: la sala se cancela y
 * se reembolsa a los participantes.
 */
class TriviaInsufficientQuestionsTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    protected function setUp() : void
    {
        parent::setUp();
        (new MovementTypeSeeder)->run();
        MovementType::forgetCatalogCache();
        Redis::flushall();
        Queue::fake();
    }

    public function test_start_with_enough_questions_plays_normally() : void
    {
        [$room, $wallet] = $this->roomWithPaidPlayer(questionCount: 2, activeQuestions: 2, fee: 10.0);

        $state = app(StartGameAction::class)->execute((string) $room->id, null, []);

        $this->assertSame('active', $state->status);
        $this->assertSame('playing', $room->fresh()->status);
        $this->assertSame(90.0, (float) $wallet->fresh()->balance);
    }

    public function test_start_without_enough_questions_cancels_room_and_refunds_entries() : void
    {
        [$room, $wallet] = $this->roomWithPaidPlayer(questionCount: 5, activeQuestions: 2, fee: 10.0);
        $this->assertSame(90.0, (float) $wallet->fresh()->balance);

        try {
            app(StartGameAction::class)->execute((string) $room->id, null, []);
            $this->fail('Debió rechazar el inicio por falta de preguntas');
        } catch (TriviaGameException $e) {
            $this->assertStringContainsString('2 de 5', $e->getMessage());
        }

        $this->assertSame('cancelled', $room->fresh()->status);
        $this->assertSame(100.0, (float) $wallet->fresh()->balance);
        $this->assertTrue(WalletMovement::where('wallet_id', $wallet->id)->where('type', WalletMovement::TYPE_REFUND)->exists());
    }

    /** @return array{0: TriviaRoom, 1: Wallet} */
    private function roomWithPaidPlayer(int $questionCount, int $activeQuestions, float $fee) : array
    {
        $user = User::factory()->create();
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
        $wallet = Wallet::create([
            'user_id' => $user->id, 'currency_id' => $currency->id,
            'balance' => 100.0, 'bonus_balance' => 0, 'status' => 'ACTIVE',
        ]);

        $room = TriviaRoom::create([
            'code' => 'T5' . strtoupper(Str::random(4)),
            'title' => 'Sala T5',
            'host_id' => '1',
            'status' => 'waiting',
            'max_players' => 10,
            'question_count' => $questionCount,
            'entry_fee' => $fee,
            'entry_currency_id' => (string) $currency->id,
        ]);

        app(WalletService::class)->recordTriviaEntry($wallet, $fee, 'Entrada sala Trivia', (string) $room->id, $room->title);

        DB::connection('pgsql_trivia')->table('trivia_participants')->insert([
            'id' => (string) Str::random(20),
            'room_id' => (string) $room->id,
            'user_id' => (string) $user->id,
            'status' => 'confirmed',
            'paid_amount' => $fee,
            'paid_currency_id' => (string) $currency->id,
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 1; $i <= $activeQuestions; $i++) {
            TriviaQuestion::create([
                'question_text' => "Pregunta {$i}",
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option_index' => 0,
                'is_active' => true,
            ]);
        }

        return [$room, $wallet];
    }
}
