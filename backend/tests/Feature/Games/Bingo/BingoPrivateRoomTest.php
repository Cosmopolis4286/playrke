<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Services\Room\BingoPrivateRoomAccess;
use App\Domains\Games\Bingo\Services\Room\BingoRoomCrudService;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Models\Game;
use App\Models\Games\Common\ConnectionType\ConnectionType;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Room\RoomType;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Salas privadas (código + contraseña): código único entre salas abiertas,
 * no se activan sin credenciales, quien compró cartones vuelve sin reingresar
 * el código, y límite de intentos fallidos por jugador y por sala.
 */
class BingoPrivateRoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void
    {
        parent::setUp();
        Cache::flush();
        GameStatus::forgetCache();
        foreach (['ACTIVE', 'INACTIVE', 'IN_PROGRESS', 'FINISHED', 'CANCELLED', 'DELETED'] as $code) {
            GameStatus::firstOrCreate(['code' => $code], ['label' => $code]);
        }
    }

    #[Test]
    public function copiar_una_sala_privada_le_da_un_codigo_nuevo_y_no_la_deja_activable_sin_contrasena() : void
    {
        $room = $this->privateRoom('ORIGEN');
        $this->actAs($room->creator);

        app(BingoRoomCrudService::class)->copyRoom($room);

        $copy = BingoRoom::where('id', '!=', $room->id)->latest('id')->with('config')->firstOrFail();
        $this->assertNotSame('ORIGEN', $copy->config->access_code);
        $this->assertNotEmpty($copy->config->access_code);
        $this->assertNull($copy->config->room_password);

        $this->expectExceptionMessage('BINGO_ROOM_PRIVATE_MISSING_CREDENTIALS');
        app(BingoPrivateRoomAccess::class)->assertHasCredentials($copy);
    }

    #[Test]
    public function el_acierto_limpia_los_fallos_y_el_codigo_no_distingue_mayusculas() : void
    {
        $room = $this->privateRoom('LIMPIA');
        $this->actAs(User::factory()->create());
        $crud = app(BingoRoomCrudService::class);

        for ($i = 0; $i < 4; $i++) {
            $crud->verifyRoomAccess($room, 'LIMPIA', 'mala');
        }
        $this->assertTrue($crud->verifyRoomAccess($room, 'limpia', 'secreta123')['verified']);

        $this->assertTrue($crud->joinRoomByCode(' limpia ', 'secreta123')['verified'], 'Tras acertar, el contador vuelve a cero');
    }

    #[Test]
    public function el_codigo_es_unico_entre_salas_privadas_abiertas_sin_importar_mayusculas() : void
    {
        $room = $this->privateRoom('FIESTA');
        $access = app(BingoPrivateRoomAccess::class);

        $this->assertTrue($access->isAccessCodeTaken('fiesta'));
        $this->assertFalse($access->isAccessCodeTaken('FIESTA', (string) $room->id), 'La propia sala no cuenta');

        $room->update(['status_id' => GameStatus::codeToId('FINISHED')]);
        $this->assertFalse($access->isAccessCodeTaken('FIESTA'), 'Una sala terminada libera su código');

        $generated = $access->generateUniqueCode();
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $generated, 'Sin 0/O ni 1/I/L');
    }

    #[Test]
    public function el_formulario_rechaza_un_codigo_en_uso_y_activar_una_copia_sin_contrasena() : void
    {
        $existing = $this->privateRoom('OCUPADO');
        $privateTypeId = (string) $existing->config->room_type_id;

        $errors = $this->validateRoomRequest(['roomType' => $privateTypeId, 'accessCode' => 'ocupado', 'roomPassword' => 'secreta123']);
        $this->assertSame('Ese código ya lo usa otra sala privada. Elige otro.', $errors->first('accessCode'));

        // Editar la propia sala con su mismo código está bien.
        $errors = $this->validateRoomRequest(['roomType' => $privateTypeId, 'accessCode' => 'OCUPADO'], $existing);
        $this->assertFalse($errors->has('accessCode'));

        // Una copia (sin contraseña) no se activa sin definirla.
        $copy = $this->privateRoom('COPIA01', password: null, status: 'INACTIVE');
        $errors = $this->validateRoomRequest(['roomType' => $privateTypeId, 'accessCode' => 'COPIA01', 'activateRoom' => true], $copy);
        $this->assertSame('Define una contraseña para activar la sala privada.', $errors->first('roomPassword'));
    }

    #[Test]
    public function en_la_sala_de_espera_basta_la_contrasena_pero_un_codigo_enviado_se_valida() : void
    {
        $room = $this->privateRoom('SOLOPASS');
        $this->actAs(User::factory()->create());
        $crud = app(BingoRoomCrudService::class);

        $this->assertFalse($crud->verifyRoomAccess($room, '', 'mala')['verified']);
        $this->assertFalse($crud->verifyRoomAccess($room, 'OTRO', 'secreta123')['verified'], 'Código enviado y erróneo');
        $this->assertTrue($crud->verifyRoomAccess($room, '', 'secreta123')['verified']);
    }

    #[Test]
    public function muchas_cuentas_probando_bloquean_la_sala_y_avisan_al_creador() : void
    {
        $room = $this->privateRoom('ATAQUE');
        $crud = app(BingoRoomCrudService::class);

        // 30 fallos repartidos en 10 cuentas (3 cada una: ninguna llega a su propio bloqueo).
        for ($account = 0; $account < 10; $account++) {
            $this->actAs(User::factory()->create());
            for ($i = 0; $i < 3; $i++) {
                $crud->verifyRoomAccess($room, 'ATAQUE', 'mala');
            }
        }

        $this->actAs(User::factory()->create()); // una cuenta nueva, con la contraseña correcta

        try {
            $crud->verifyRoomAccess($room, 'ATAQUE', 'secreta123');
            $this->fail('La sala debió quedar bloqueada');
        } catch (BingoRoomException $e) {
            $this->assertSame('BINGO_ROOM_PRIVATE_ACCESS_LOCKED', $e->errorCode());
        }

        $this->assertSame(1, Notification::where('user_id', $room->creator_user_id)->count(), 'Un aviso por ventana de bloqueo');

        // El creador sigue entrando a su sala.
        $this->actAs($room->creator);
        $this->assertTrue($crud->canPlayerAccessRoom($room->fresh()));

        // Pasado el bloqueo, se puede entrar de nuevo.
        RateLimiter::clear('bingo-private-fail:room:' . $room->id);
        $this->actAs(User::factory()->create());
        $this->assertTrue($crud->verifyRoomAccess($room, 'ATAQUE', 'secreta123')['verified']);
    }

    #[Test]
    public function quien_ya_compro_cartones_entra_sin_reingresar_el_codigo() : void
    {
        $room = $this->privateRoom('VUELVO');
        $player = User::factory()->create();
        $this->actAs($player);
        $crud = app(BingoRoomCrudService::class);

        $this->assertFalse($crud->isRoomAccessVerified($room));

        BingoRoomUser::create(['room_id' => $room->id, 'user_id' => $player->id, 'cards_purchased' => 2]);
        $this->assertTrue($crud->isRoomAccessVerified($room->fresh()), 'Otra sesión/dispositivo: sigue entrando');
    }

    #[Test]
    public function un_jugador_queda_bloqueado_tras_5_intentos_fallidos() : void
    {
        $room = $this->privateRoom('BLOQ01');
        $player = User::factory()->create();
        $this->actAs($player);
        $crud = app(BingoRoomCrudService::class);

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($crud->verifyRoomAccess($room, 'BLOQ01', 'mala')['verified']);
        }

        // Ni con la contraseña correcta: bloqueado por 15 minutos.
        try {
            $crud->verifyRoomAccess($room, 'BLOQ01', 'secreta123');
            $this->fail('Debió quedar bloqueado');
        } catch (BingoRoomException $e) {
            $this->assertSame('BINGO_ROOM_PRIVATE_ACCESS_LOCKED', $e->errorCode());
            $this->assertSame(429, $e->httpStatus());
        }

        // Tampoco puede seguir probando por "Tengo un código".
        $this->expectExceptionMessage('BINGO_ROOM_PRIVATE_ACCESS_LOCKED');
        $crud->joinRoomByCode('BLOQ01', 'secreta123');
    }

    #[Test]
    public function una_sala_privada_sin_contrasena_no_se_puede_activar() : void
    {
        $room = $this->privateRoom('SINPASS', password: null, status: 'INACTIVE');
        $this->actAs($room->creator);

        try {
            app(BingoRoomCrudService::class)->toggleStatus($room);
            $this->fail('No debió activarse sin contraseña');
        } catch (BingoRoomException $e) {
            $this->assertSame('BINGO_ROOM_PRIVATE_MISSING_CREDENTIALS', $e->errorCode());
        }
        $this->assertSame('INACTIVE', $room->fresh()->status->code);

        $room->config->update(['room_password' => Hash::make('secreta123')]);
        $this->assertTrue(app(BingoRoomCrudService::class)->toggleStatus($room->fresh()));
        $this->assertSame('ACTIVE', $room->fresh()->status->code);
    }

    private function actAs(User $user) : void
    {
        Auth::setUser($user);
    }

    private function privateRoom(string $code, ?string $password = 'secreta123', string $status = 'ACTIVE') : BingoRoom
    {
        $creator = User::factory()->create();
        $game = Game::firstOrCreate(['name' => 'BINGO'], ['status' => 'ACTIVE']);

        $room = BingoRoom::create([
            'uuid_code' => (string) Str::uuid(),
            'status_id' => GameStatus::codeToId($status),
            'creator_user_id' => $creator->id,
        ]);

        BingoRoomConfig::create([
            'room_id' => $room->id,
            'room_type_id' => RoomType::firstOrCreate(['code' => 'PRIVATE'], ['label' => 'Privada'])->id,
            'creator_user_id' => $creator->id,
            'connection_type_id' => ConnectionType::firstOrCreate(['code' => 'ONLINE'], ['label' => 'Online'])->id,
            'prize_pool_type_id' => PrizePoolType::firstOrCreate(
                ['game_id' => $game->id, 'code' => 'STANDARD'],
                ['label' => 'Estándar', 'is_accumulative' => false],
            )->id,
            'game_variant' => BingoVariant::BINGO_75,
            'start_mode' => 'MANUAL',
            'access_code' => $code,
            'room_password' => $password ? Hash::make($password) : null,
        ]);

        return $room->fresh(['config.roomType', 'status', 'creator']);
    }

    private function validateRoomRequest(array $data, ?BingoRoom $room = null) : \Illuminate\Support\MessageBag
    {
        $request = \App\Http\Requests\Games\Bingo\Room\BingoRoomRequest::create('/bingo-rooms', $room ? 'PUT' : 'POST', $data);
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));
        if ($room) {
            $request->setRouteResolver(fn () => tap(new \Illuminate\Routing\Route('PUT', 'bingo-rooms/{room}', []), fn ($route) => $route->bind($request)->setParameter('room', $room)));
        }
        (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
        $validator = (new ReflectionMethod(\Illuminate\Foundation\Http\FormRequest::class, 'getValidatorInstance'))->invoke($request);
        $validator->passes();

        return $validator->errors();
    }
}
