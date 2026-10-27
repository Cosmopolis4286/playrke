<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Services\Room\BingoRoomCrudService;
use App\Models\Games\Common\Room\RoomType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * BingoRoomCrudService::canPlayerAccessRoom(): chequeo único de acceso por tipo
 * de sala para los endpoints de jugador (lobby, precio, compra). Antes VIP y
 * DEMO solo estaban ocultas del listado: con el enlace cualquiera entraba y
 * compraba con dinero real.
 */
class BingoRoomAccessByTypeTest extends TestCase
{
    private string $creatorId = '960000000000000001';
    private string $playerId = '960000000000000002';

    #[Test]
    public function el_creador_siempre_entra_a_su_sala() : void
    {
        $this->actAs($this->creatorId);

        foreach (['PUBLIC', 'PRIVATE', 'VIP', 'DEMO'] as $type) {
            $this->assertTrue($this->crud()->canPlayerAccessRoom($this->room($type)), "creador en {$type}");
        }
    }

    #[Test]
    public function un_jugador_entra_a_publicas_y_a_privadas_solo_verificadas() : void
    {
        $this->actAs($this->playerId);

        $this->assertTrue($this->crud()->canPlayerAccessRoom($this->room('PUBLIC')));

        $private = $this->room('PRIVATE');
        $this->assertFalse($this->crud()->canPlayerAccessRoom($private));
        session([BingoRoomCrudService::SESSION_VERIFIED_ROOMS_KEY => [(string) $private->id]]);
        $this->assertTrue($this->crud()->canPlayerAccessRoom($private));
    }

    #[Test]
    public function un_tipo_desconocido_queda_cerrado() : void
    {
        $this->actAs($this->playerId);
        $this->assertFalse($this->crud()->canPlayerAccessRoom($this->room('NUEVO_TIPO')));
    }

    #[Test]
    public function vip_y_demo_quedan_cerradas_para_jugadores_aunque_tengan_el_enlace() : void
    {
        $this->actAs($this->playerId);

        foreach (['VIP', 'DEMO'] as $type) {
            $room = $this->room($type);
            $this->assertFalse($this->crud()->canPlayerAccessRoom($room), "{$type} no debe abrirse por enlace");
            $this->assertTrue($this->crud()->isRestrictedRoomType($room));

            try {
                $this->crud()->assertPlayerCanAccessRoom($room);
                $this->fail("Se esperaba bloqueo para {$type}");
            } catch (BingoRoomException $e) {
                $this->assertSame(404, $e->httpStatus(), 'Se responde como sala inexistente');
                $this->assertSame('BINGO_ROOM_NOT_ACCESSIBLE', $e->errorCode());
            }
        }
    }

    private function actAs(string $userId) : void
    {
        $user = new User;
        $user->id = $userId;
        Auth::setUser($user);
    }

    private function crud() : BingoRoomCrudService
    {
        return app(BingoRoomCrudService::class);
    }

    private function room(string $typeCode) : BingoRoom
    {
        $room = new BingoRoom(['creator_user_id' => $this->creatorId]);
        $room->id = '960000000000000100';
        $config = new BingoRoomConfig;
        $config->setRelation('roomType', new RoomType(['code' => $typeCode]));
        $room->setRelation('config', $config);

        return $room;
    }
}
