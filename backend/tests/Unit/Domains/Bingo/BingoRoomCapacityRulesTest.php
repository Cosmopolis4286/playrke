<?php

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\ValueObjects\BingoRoomCapacityRules as R;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BingoRoomCapacityRulesTest extends TestCase
{
    #[Test]
    public function configuracion_coherente_no_da_errores() : void
    {
        $this->assertSame([], R::crossErrors(minCards: 10, maxCards: 500, minPlayers: 5, maxPlayers: 100, gameModeCode: 'MULTI'));
        $this->assertSame([], R::crossErrors(minCards: 2, maxCards: null, minPlayers: 2, maxPlayers: null, gameModeCode: null));
    }

    #[Test]
    public function max_cartones_debe_cubrir_el_cupo_de_jugadores() : void
    {
        $errors = R::crossErrors(2, 50, 2, 100, 'MULTI');
        $this->assertArrayHasKey('maxCardsToStart', $errors);
        $this->assertStringContainsString('cupo', $errors['maxCardsToStart']);
    }

    #[Test]
    public function maximos_no_pueden_ser_menores_a_sus_minimos() : void
    {
        $this->assertArrayHasKey('maxCardsToStart', R::crossErrors(10, 5, 2, null, 'MULTI'));
        $this->assertArrayHasKey('maxPlayers', R::crossErrors(10, null, 8, 4, 'MULTI'));
    }

    #[Test]
    public function min_cartones_no_puede_ser_menor_a_min_jugadores() : void
    {
        $this->assertArrayHasKey('minCardsToStart', R::crossErrors(3, null, 5, null, 'MULTI'));
    }

    #[Test]
    public function un_solo_jugador_solo_se_permite_en_solitario() : void
    {
        $this->assertArrayHasKey('minPlayers', R::crossErrors(1, null, 1, null, 'MULTI'));
        $this->assertArrayHasKey('minPlayers', R::crossErrors(1, null, 1, null, null));
        $this->assertSame([], R::crossErrors(1, null, 1, null, R::GAME_MODE_SOLO));
    }
}
