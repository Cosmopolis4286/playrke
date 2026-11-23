<?php

namespace Tests\Feature\Games\Trivia;

use App\Domains\Games\Trivia\Actions\JoinRoomAction;
use App\Domains\Games\Trivia\Actions\StartGameAction;
use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\TriviaPowerUpService;
use App\Events\Games\Trivia\TriviaQuestionClosed;
use App\Http\Middleware\RoleMiddleware;
use App\Jobs\Games\Trivia\AdvanceTriviaQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Ciclo de vida de cada pregunta: abierta → cerrada (revelación) → siguiente.
 */
class TriviaQuestionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    private string $gameId;

    private TriviaRoom $room;

    /** @var array<int, User> */
    private array $players;

    protected function setUp() : void
    {
        parent::setUp();
        Redis::flushall();
        // Los jobs del ciclo (cierre por tiempo / avance tras revelar) se
        // verifican como despachos; no deben correr solos en la prueba.
        Queue::fake();
        $this->withoutMiddleware(RoleMiddleware::class);

        $this->room = TriviaRoom::create([
            'code' => 'LC' . strtoupper(Str::random(4)),
            'title' => 'Ciclo de pregunta',
            'host_id' => '1',
            'status' => 'waiting',
            'max_players' => 10,
            'question_count' => 2,
            'time_per_question' => 30,
        ]);

        foreach ([1, 2] as $n) {
            TriviaQuestion::create([
                'question_text' => "Pregunta {$n}",
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option_index' => 2,
                'time_limit_seconds' => 15,
                'is_active' => true,
            ]);
        }

        $this->players = [User::factory()->create(), User::factory()->create()];
        $join = app(JoinRoomAction::class);
        foreach ($this->players as $i => $player) {
            $join->execute((string) $this->room->id, (string) $player->id, "Jugador {$i}");
        }

        $this->gameId = app(StartGameAction::class)->execute((string) $this->room->id, null, [])->gameId;
    }

    public function test_room_time_per_question_overrides_question_default_and_deadline_is_sent() : void
    {
        $current = $this->currentQuestion();

        $this->assertSame(30, (int) $current['time_limit_seconds']);

        Queue::assertPushed(AdvanceTriviaQuestion::class, fn ($job) => $job->phase === AdvanceTriviaQuestion::PHASE_CLOSE
            && $job->questionId === (string) $current['question_id']);
    }

    public function test_last_answer_closes_question_with_reveal_instead_of_jumping_to_next() : void
    {
        Event::fake([TriviaQuestionClosed::class]);
        $current = $this->currentQuestion();
        $questionId = (string) $current['question_id'];

        usleep(350_000); // por encima del mínimo humano anti-bot
        $this->answer($this->players[0], $questionId, 2)->assertOk()->assertJson(['question_id' => $questionId, 'is_correct' => true]);
        Event::assertNotDispatched(TriviaQuestionClosed::class);

        $this->answer($this->players[1], $questionId, 1)->assertOk();

        Event::assertDispatched(TriviaQuestionClosed::class, function (TriviaQuestionClosed $e) use ($questionId) {
            return $e->questionId === $questionId
                && $e->correctOptionIndex === 2
                && $e->distribution === [0, 1, 1, 0]
                && $e->answeredCount === 2
                && $e->reason === 'all_answered';
        });

        // La siguiente pregunta llega después de la revelación, no en el acto.
        $this->assertSame($questionId, (string) $this->currentQuestion()['question_id']);
        Queue::assertPushed(AdvanceTriviaQuestion::class, fn ($job) => $job->phase === AdvanceTriviaQuestion::PHASE_ADVANCE
            && $job->questionId === $questionId);
    }

    public function test_answers_and_power_ups_are_rejected_once_the_question_is_closed() : void
    {
        $questionId = (string) $this->currentQuestion()['question_id'];

        $this->assertTrue(app(GameEngineInterface::class)->closeQuestion($this->gameId, $questionId, 'timeout'));

        usleep(350_000);
        $this->answer($this->players[0], $questionId, 2)->assertStatus(409);
        $this->actingAs($this->players[0])
            ->postJson(route('trivia-games.game.power-up', $this->gameId), ['type' => 'fifty_fifty'])
            ->assertStatus(409);
    }

    public function test_close_question_is_idempotent() : void
    {
        Event::fake([TriviaQuestionClosed::class]);
        $questionId = (string) $this->currentQuestion()['question_id'];
        $engine = app(GameEngineInterface::class);

        $this->assertTrue($engine->closeQuestion($this->gameId, $questionId, 'timeout'));
        $this->assertFalse($engine->closeQuestion($this->gameId, $questionId, 'timeout'));
        $this->assertFalse($engine->closeQuestion($this->gameId, 'otra-pregunta', 'timeout'));

        Event::assertDispatchedTimes(TriviaQuestionClosed::class, 1);
    }

    public function test_advance_phase_sends_the_next_question_after_reveal() : void
    {
        $questionId = (string) $this->currentQuestion()['question_id'];
        app(GameEngineInterface::class)->closeQuestion($this->gameId, $questionId, 'timeout');

        $this->runJob(new AdvanceTriviaQuestion($this->gameId, $questionId, AdvanceTriviaQuestion::PHASE_ADVANCE));

        $this->assertNotSame($questionId, (string) $this->currentQuestion()['question_id']);
    }

    public function test_close_phase_waits_for_a_pending_extra_time_bonus() : void
    {
        Event::fake([TriviaQuestionClosed::class]);
        $current = $this->currentQuestion();
        $questionId = (string) $current['question_id'];

        // Simula que el tiempo base ya venció, pero un jugador sin responder
        // activó +5 s: el cierre debe esperar su plazo extendido.
        $current['server_timestamp_ms'] = microtime(true) * 1000 - 30_500;
        app(GameStateRepositoryInterface::class)->saveCurrentQuestion($this->gameId, $current);
        app(TriviaPowerUpService::class)->activateExtraTime($this->gameId, (string) $this->players[1]->id, $questionId);

        $job = (new AdvanceTriviaQuestion($this->gameId, $questionId))->withFakeQueueInteractions();
        $this->runJob($job);

        $job->assertReleased();
        Event::assertNotDispatched(TriviaQuestionClosed::class);

        // Vencido también el plazo extendido: ahora sí cierra.
        $current['server_timestamp_ms'] = microtime(true) * 1000 - 36_000;
        app(GameStateRepositoryInterface::class)->saveCurrentQuestion($this->gameId, $current);
        $this->runJob(new AdvanceTriviaQuestion($this->gameId, $questionId));

        Event::assertDispatched(TriviaQuestionClosed::class, fn ($e) => $e->reason === 'timeout');
    }

    public function test_play_page_restores_deadline_answer_and_score_after_reload() : void
    {
        $questionId = (string) $this->currentQuestion()['question_id'];

        usleep(350_000);
        $this->answer($this->players[0], $questionId, 2)->assertOk();

        $this->actingAs($this->players[0])
            ->get(route('trivia.play', $this->gameId))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Games/Trivia/Play')
                ->where('question.id', $questionId)
                ->where('question.timeLimit', 30)
                ->has('question.deadlineMs')
                ->has('serverNowMs')
                ->where('myAnswer.selected_option_index', 2)
                ->where('myAnswer.is_correct', true)
                ->where('reveal', null)
                ->where('score', fn ($score) => $score > 0));
    }

    public function test_play_page_does_not_leak_the_correct_answer_before_answering() : void
    {
        $this->actingAs($this->players[1])
            ->get(route('trivia.play', $this->gameId))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('myAnswer', null)
                ->where('reveal', null)
                ->missing('question.correctOptionIndex'));
    }

    private function currentQuestion() : array
    {
        $current = app(GameStateRepositoryInterface::class)->getCurrentQuestion($this->gameId);
        $this->assertNotNull($current);

        return $current;
    }

    private function answer(User $player, string $questionId, int $option)
    {
        return $this->actingAs($player)->postJson(route('trivia-games.game.answer', $this->gameId), [
            'question_id' => $questionId,
            'selected_option_index' => $option,
            'response_time_ms' => 400,
        ]);
    }

    private function runJob(AdvanceTriviaQuestion $job) : void
    {
        app()->call([$job, 'handle']);
    }
}
