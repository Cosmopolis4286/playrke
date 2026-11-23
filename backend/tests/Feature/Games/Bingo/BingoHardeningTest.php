<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Endurecimiento previo al lanzamiento: deploy.sh no corta sorteos en curso
 * (bingo:active-draws), las lecturas de la partida tienen límite de
 * peticiones y los cartones se siguen generando válidos con el CSPRNG.
 */
class BingoHardeningTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function active_draws_cuenta_solo_las_salas_en_curso() : void
    {
        GameStatus::forgetCache();
        foreach (['ACTIVE', 'IN_PROGRESS', 'FINISHED'] as $code) {
            GameStatus::firstOrCreate(['code' => $code], ['label' => $code]);
        }
        $creator = User::factory()->create();
        foreach (['IN_PROGRESS', 'IN_PROGRESS', 'ACTIVE', 'FINISHED'] as $code) {
            BingoRoom::create(['uuid_code' => (string) Str::uuid(), 'status_id' => GameStatus::codeToId($code), 'creator_user_id' => $creator->id]);
        }

        $this->artisan('bingo:active-draws')->expectsOutput('2')->assertSuccessful();
    }

    #[Test]
    public function las_lecturas_de_la_partida_tienen_limite_de_peticiones() : void
    {
        foreach (['bingo.draw-state', 'bingo.user-cards', 'bingo.lobby-players', 'bingo.view-card'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $this->assertContains('throttle:bingo-game-read', $route->gatherMiddleware(), $name);
        }
    }

    #[Test]
    public function los_cartones_generados_siguen_siendo_validos() : void
    {
        for ($i = 0; $i < 50; $i++) {
            $matrix = BingoVariant::generateCardMatrix(BingoVariant::BINGO_75);
            $numbers = array_values(array_filter(array_merge(...array_values($matrix)), fn ($n) => $n !== null));

            $this->assertCount(24, $numbers, 'BINGO_75: 24 números + la casilla libre');
            $this->assertSame($numbers, array_values(array_unique($numbers)), 'Sin repetidos');
            foreach (array_values($matrix) as $col => $values) {
                foreach (array_filter($values, fn ($n) => $n !== null) as $n) {
                    $this->assertGreaterThanOrEqual($col * 15 + 1, $n);
                    $this->assertLessThanOrEqual($col * 15 + 15, $n);
                }
            }
        }
    }
}
