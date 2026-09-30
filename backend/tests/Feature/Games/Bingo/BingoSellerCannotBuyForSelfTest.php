<?php

namespace Tests\Feature\Games\Bingo;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regla de negocio: comercios y vendedores NO generan cartones para sí mismos;
 * solo venden a jugadores externos desde el punto de venta (generate, con
 * comprobante que el comercio aprueba). Existía POST bingo/game-rooms/{room}/buy
 * (buyFromGameRooms) que les generaba cartones a su propio nombre sin cobro ni
 * comprobante — la sala sumaba bote y, si ganaban, cobraban un premio creado de
 * la nada. Este test impide que esa vía vuelva a aparecer.
 */
class BingoSellerCannotBuyForSelfTest extends TestCase
{
    #[Test]
    public function no_existe_ruta_de_compra_para_comercio_o_vendedor(): void
    {
        $this->assertFalse(Route::has('bingo-game-rooms.buy-from-rooms'));

        $selfBuy = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'bingo/game-rooms/{room}/buy');
        $this->assertNull($selfBuy, 'La compra desde salas es solo para jugadores (player-buy).');

        // La compra del jugador con su billetera sigue existiendo.
        $this->assertTrue(Route::has('bingo-game-rooms.player-buy'));
    }
}
