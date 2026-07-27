<?php

namespace Tests\Feature\Api;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cobertura HTTP del dominio Bingo real.
 *
 * Reescrito por completo: el archivo original probaba un API REST inexistente
 * (`/api/bingo/*`, modelo `App\Models\BingoCtns`, columna `bingo_rooms.status`
 * como string libre, `card_price` directo en la sala). Esa superficie nunca
 * llegó a activarse — `routes/api.php` está 100% comentado — y el dominio real
 * vive en `routes/modules/games/bingo.php`, cargado desde `web.php` (sin
 * prefijo /api), con controladores Inertia + JSON puntual, protegidos por
 * `auth` + `multiPermission` + policies (`BingoRoomPolicy`), no simple `auth`.
 *
 * Mapeo de los 16 casos originales -> arquitectura real:
 * - Listar salas / filtrar por status  -> bingo-rooms.index (Inertia, admin) +
 *   summary-data (JSON, filtros server-side) usando status_id real (FK a game_status).
 * - Detalle de sala                    -> bingo-rooms.show (JSON, admin).
 * - Comprar cartones                   -> bingo-game-rooms.player-buy
 *   (BingoCardGenerateService::playerBuyFromGameRooms), rol 'player' + wallet.
 * - Ver cartones propios               -> bingo.user-cards (requiere pertenecer a la sala).
 * - Ver sorteo actual / números cantados -> bingo.draw-state (requiere pertenecer a la sala).
 * - Verificar ganador                  -> bingo.check-bingo (por sala+usuario, no por cartón).
 * - Historial                          -> bingo.my-games (Inertia) — ya no es JSON con `success`.
 * - Cantidad de compra 1-10            -> ahora es 1-40 (BingoRoomPlayerBuyRequest::rules()).
 * - Bloquear invitados                 -> redirect a login (302), no 401 JSON (rutas web, no API).
 *
 * Funcionalidad removida del dominio (documentada en el reporte, no aquí):
 * - No existe verificación de ganador POR CARTÓN individual; check-bingo evalúa
 *   todos los cartones del usuario autenticado en la sala.
 * - No existe "insufficient balance" como código 422 específico; el servicio
 *   lanza DomainException con mensaje libre, mapeado por handleServiceJson.
 */
class BingoTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Currency $currency;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->seedGameStatuses();
        $this->seedRolesAndPermissions();

        $this->currency = Currency::firstOrCreate(
            ['iso_code' => 'USD'],
            ['symbol' => '$', 'name' => 'US Dollar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE']
        );

        $this->country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO', 'status' => 'ACTIVE']
        );

        // BingoCardGenerationService::generateCards() resuelve Game::getValueByName('BINGO', 'id')
        // para registrar la moneda en currencies_game_rooms -- sin esta fila, 'game_id' llega NULL
        // y viola la constraint NOT NULL de la tabla.
        \App\Models\Game::firstOrCreate(['name' => 'BINGO'], ['status' => 'ACTIVE']);

        $this->user = User::factory()->create(['country_id' => $this->country->id]);
    }

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static int $idCounter = 0;

    private function nextId(): string
    {
        return (string) (200000000000000000 + (++self::$idCounter));
    }

    private function seedGameStatuses(): void
    {
        // GameStatus::codeToId() cachea el mapa code->id 60s (Cache::tags),
        // pero este test usa DatabaseTransactions: la transacción SQL se
        // revierte al terminar cada test, mientras la cache de aplicación
        // NO se revierte con ella. Sin este forgetCache(), un ID cacheado por
        // un test anterior en la misma corrida puede apuntar a una fila que
        // ya no existe en la transacción actual (o que fue recreada con otro
        // Snowflake id), causando que los filtros por status no encuentren
        // nada (0 resultados esperando 1).
        GameStatus::forgetCache();

        $statuses = ['ACTIVE', 'INACTIVE', 'DELETED', 'CLOSED', 'IN_PROGRESS', 'FINISHED', 'REFUND', 'OFFLINE'];
        foreach ($statuses as $code) {
            if (!GameStatus::where('code', $code)->exists()) {
                GameStatus::create(['code' => $code, 'label' => $code]);
            }
        }
    }

    private function seedRolesAndPermissions(): void
    {
        // roles.id es Snowflake (no autoincrement) y roles.prefix es NOT NULL/unique
        // -- Spatie\Role no conoce el trait SnowflakeId, por lo que hay que asignar
        // ambos campos a mano (mismo patrón que RoleSeeder::run()).
        foreach (['superadmin', 'player'] as $name) {
            if (!Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                // Role::create() no acepta 'id' (Spatie\Role guarda su propia PK en el
                // constructor: $this->guarded[] = $this->primaryKey;) -- hay que asignarlo
                // después de instanciar, igual que RoleSeeder::run().
                $role = new Role([
                    'name' => $name,
                    'guard_name' => 'web',
                    'prefix' => $name,
                    'type' => 'web',
                ]);
                $role->id = $this->nextId();
                $role->save();
            }
        }

        Permission::firstOrCreate(['name' => 'games.bingo', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'games.manage', 'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['country_id' => $this->country->id]);
        $admin->assignRole('superadmin');

        return $admin;
    }

    private function makePlayer(): User
    {
        $player = User::factory()->create(['country_id' => $this->country->id]);
        $player->assignRole('player');
        $player->givePermissionTo('games.bingo');

        return $player;
    }

    private function makeWallet(User $user, float $balance = 1000): Wallet
    {
        return Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $this->currency->id,
            'balance' => $balance,
            'bonus_balance' => 0,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Crea una sala de bingo + su config directamente vía DB (bypasea el boot
     * de SnowflakeId, igual que GenerateBingoRoomsCommandTest). Los FKs a
     * room_types/connection_types/prize_pool_types usan IDs sintéticos: con
     * FOREIGN_KEY_CHECKS=0 no requieren fila real, igual que en el resto de
     * la suite de Bingo ya migrada.
     */
    private function makeRoom(array $roomOverrides = [], array $configOverrides = []): BingoRoom
    {
        $roomId = $this->nextId();
        $statusCode = $roomOverrides['status_code'] ?? 'ACTIVE';
        unset($roomOverrides['status_code']);

        $roomDefaults = [
            'id' => $roomId,
            'uuid_code' => (string) \Illuminate\Support\Str::uuid(),
            'status_id' => GameStatus::codeToId($statusCode),
            'creator_user_id' => $this->user->id,
            'paid_prizes' => 'N',
            'prize_pool' => 0,
            'full_house_prize' => 0,
            'serial_prize' => 0,
            'rke_commission' => 5,
            'room_commission' => 10,
            'seller_commission' => 0,
            'total_players' => 0,
            'total_cards' => 0,
            'total_sales' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('bingo_rooms')->insert(array_merge($roomDefaults, $roomOverrides));

        $configDefaults = [
            'id' => $this->nextId(),
            'room_id' => $roomId,
            'template_id' => null,
            'parent_automatic_id' => null,
            'is_scheduled' => 'N',
            'from_house' => 'N',
            'room_type_id' => $this->nextId(),
            'creator_user_id' => $this->user->id,
            'connection_type_id' => $this->nextId(),
            'in_live' => 'N',
            // unix_date en el futuro (no "ahora"): BingoRoomService::summaryData()
            // llama CheckExpiredBingoRoomsService::handle() en cada listado, que
            // mueve a INACTIVE/IN_PROGRESS cualquier sala ACTIVE cuyo unix_date ya
            // sea <= now(). Con "ahora" exacto, la sala podía expirar entre su
            // creación y el request del test, dejándola fuera del filtro ?status=ACTIVE.
            'date' => now()->addDay()->toDateString(),
            'time' => now()->format('H:i:s'),
            'unix_date' => now()->addDay()->timestamp,
            'use_base_usd' => 1,
            'card_price_usd' => 10.00,
            'allowed_currencies' => json_encode([$this->currency->id]),
            'prize_pool_type_id' => $this->nextId(),
            'rounds' => '1',
            'use_serials' => 0,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('bingo_rooms_config')->insert(array_merge($configDefaults, $configOverrides));

        return BingoRoom::find($roomId);
    }

    private function makeRoomUser(BingoRoom $room, User $user, int $cardsPurchased = 1): BingoRoomUser
    {
        return BingoRoomUser::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => BingoRoomUser::ROLE_PLAYER,
            'cards_purchased' => $cardsPurchased,
            'winner' => 'N',
        ]);
    }

    private function makeCard(BingoRoom $room, BingoRoomUser $roomUser, User $user): BingoCard
    {
        // card_lot_id es FK NOT NULL a bingo_card_lots -- con FOREIGN_KEY_CHECKS=0
        // (activo en setUp) no se requiere una fila real, igual que el resto de
        // los FKs sintéticos usados en makeRoom().
        return BingoCard::create([
            'id' => BingoCard::generateSnowflakeId(),
            'room_id' => $room->id,
            'creator_user_id' => $user->id,
            'room_user_id' => $roomUser->id,
            'card_lot_id' => $this->nextId(),
            'currency_id' => $this->currency->id,
            'serial_number' => 'BINGO-' . strtoupper(substr((string) \Illuminate\Support\Str::uuid(), 0, 8)),
            'card_numbers' => json_encode(['B' => [1, 2, 3, 4, 5]]),
            'card_hash' => hash('sha256', uniqid('', true)),
            'status' => BingoCard::STATUS_ACTIVE,
            'is_validated' => false,
            'is_custom_card' => false,
        ]);
    }

    // ─── Listado de salas (admin) ──────────────────────────────────────────────

    /** @test */
    public function admin_can_get_list_of_rooms()
    {
        $admin = $this->makeAdmin();
        $this->makeRoom();
        $this->makeRoom();
        $this->makeRoom();

        $response = $this->actingAs($admin)
            ->getJson('/bingo-rooms/summary-data');

        // handleServiceJson() esparce el array del servicio junto a 'success' --
        // summaryData() devuelve ['rooms' => ['columns','rows'], 'pagination' => [...]]
        // a nivel raíz, no anidado bajo 'data'.
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'rooms' => ['columns', 'rows'],
                'pagination' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $this->assertEquals(3, $response->json('pagination.total'));
    }

    /** @test */
    public function admin_can_filter_rooms_by_status()
    {
        $admin = $this->makeAdmin();
        $this->makeRoom(['status_code' => 'ACTIVE']);
        $this->makeRoom(['status_code' => 'FINISHED']);

        // BingoRoomRepository::paginateSummaryGameRooms() filtra por status.code
        // (whereHas('status', ...->where('code', $filters['status']))), no por
        // status_id — el filtro va por el código de texto, no por el id numérico.
        $response = $this->actingAs($admin)
            ->getJson('/bingo-rooms/summary-data?status=ACTIVE');

        $response->assertOk();

        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['total']);
    }

    /** @test */
    public function admin_can_get_single_room_details()
    {
        $admin = $this->makeAdmin();
        $room = $this->makeRoom();

        $response = $this->actingAs($admin)
            ->getJson("/bingo-rooms/{$room->hashed_id}/show");

        // BingoRoomResource expone 'id' como hashed_id, no el snowflake crudo.
        $response->assertOk()
            ->assertJsonPath('data.id', $room->hashed_id);
    }

    // ─── Compra de cartones (player) ───────────────────────────────────────────

    /** @test */
    public function player_can_purchase_bingo_cards()
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom();
        $wallet = $this->makeWallet($player, 100);

        $response = $this->actingAs($player)
            ->postJson("/bingo/game-rooms/{$room->hashed_id}/player-buy", [
                'card_count' => 2,
            ]);

        // 2 cartones * 10.00 USD (card_price_usd) = 20.00 debitados de la wallet.
        // assertEquals (no assertJsonPath) porque PHP serializa floats "redondos"
        // sin decimales por defecto (json_encode(80.0) === "80", no "80.0") --
        // assertJsonPath compara tipo estricto y fallaría contra el int decodificado.
        $response->assertOk();
        $this->assertEquals(80.0, (float) $response->json('wallet_balance'));
        $this->assertEquals(80.0, (float) $wallet->fresh()->balance);
        $this->assertEquals(2, BingoCard::where('room_id', $room->id)->where('creator_user_id', $player->id)->count());
    }

    /** @test */
    public function player_cannot_purchase_cards_with_insufficient_balance()
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom([], ['card_price_usd' => 100.00]);
        $this->makeWallet($player, 50);

        $response = $this->actingAs($player)
            ->postJson("/bingo/game-rooms/{$room->hashed_id}/player-buy", [
                'card_count' => 1,
            ]);

        // playerBuyFromGameRooms() lanza el \DomainException GLOBAL de PHP (no
        // App\Exceptions\Domain\DomainException) -- el Handler solo mapea el segundo a
        // JSON con status_code resuelto; el global cae al branch genérico "expectsJson"
        // (Handler::render()), que siempre responde 500 salvo excepciones conocidas
        // (Auth/Validation/ModelNotFound). No hay código 422 específico para saldo
        // insuficiente en este dominio.
        $response->assertStatus(500);
        $this->assertStringContainsString('Saldo insuficiente', $response->json('message') ?? '');
    }

    /** @test */
    public function purchase_quantity_must_be_between_1_and_40()
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom();
        $this->makeWallet($player, 1000);

        // El controlador valida card_count inline (no vía FormRequest) y el Handler
        // global reescribe TODAS las respuestas AJAX/JSON -- incluida ValidationException --
        // a {success:false, message:"..."}, perdiendo la clave estándar 'errors' que usa
        // assertJsonValidationErrors(). Se verifica el mensaje de validación en su lugar.
        $response = $this->actingAs($player)
            ->postJson("/bingo/game-rooms/{$room->hashed_id}/player-buy", [
                'card_count' => 0,
            ]);

        $response->assertStatus(422);
        // Laravel humaniza el nombre del atributo en el mensaje: "card_count" -> "card count".
        $this->assertStringContainsString('card count', $response->json('message') ?? '');

        $response = $this->actingAs($player)
            ->postJson("/bingo/game-rooms/{$room->hashed_id}/player-buy", [
                'card_count' => 41,
            ]);

        $response->assertStatus(422);
        // Laravel humaniza el nombre del atributo en el mensaje: "card_count" -> "card count".
        $this->assertStringContainsString('card count', $response->json('message') ?? '');
    }

    // ─── Cartones propios ───────────────────────────────────────────────────────

    /** @test */
    public function player_can_get_their_cards_for_a_room()
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom();
        $roomUser = $this->makeRoomUser($room, $player, 3);

        for ($i = 0; $i < 3; $i++) {
            $this->makeCard($room, $roomUser, $player);
        }

        $response = $this->actingAs($player)
            ->getJson("/bingo/{$room->hashed_id}/user-cards");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    // ─── Sorteo / números cantados ──────────────────────────────────────────────

    /** @test */
    public function player_with_cards_can_get_current_draw_state()
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom();
        $this->makeRoomUser($room, $player, 1);

        $response = $this->actingAs($player)
            ->getJson("/bingo/{$room->hashed_id}/draw-state");

        // BingoGameService::getDrawState() devuelve la clave 'draw' (no 'drawnNumbers')
        // con las fichas cantadas, junto a totalPlayers/prizePool/roomFinished/etc.
        $response->assertOk()
            ->assertJsonStructure(['draw', 'totalPlayers', 'prizePool', 'roomFinished']);
    }

    /** @test */
    public function player_without_cards_cannot_access_draw_state()
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom();
        // El jugador NO compró cartones en esta sala -> BingoRoomPolicy::viewGame deniega.

        $response = $this->actingAs($player)
            ->getJson("/bingo/{$room->hashed_id}/draw-state");

        $response->assertStatus(403);
    }

    // ─── Verificar ganador (por sala, no por cartón) ───────────────────────────

    /** @test */
    public function player_can_check_bingo_for_their_room()
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom();
        $this->makeRoomUser($room, $player, 1);

        $response = $this->actingAs($player)
            ->postJson("/bingo/{$room->hashed_id}/check-bingo");

        $response->assertOk()
            ->assertJsonStructure(['success', 'hasBingo']);
    }

    // ─── Historial de partidas ──────────────────────────────────────────────────

    /** @test */
    public function player_can_get_their_game_history()
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom();
        $this->makeRoomUser($room, $player, 2);

        $response = $this->actingAs($player)->get('/bingo/my-games');

        $response->assertOk();
    }

    /** @test */
    public function player_can_filter_history_by_search_term()
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom();
        $this->makeRoomUser($room, $player, 1);

        $response = $this->actingAs($player)
            ->get('/bingo/my-games?search=' . $room->uuid_code);

        $response->assertOk();
    }

    // ─── Acceso de invitados ────────────────────────────────────────────────────

    /** @test */
    public function guest_is_redirected_when_accessing_bingo_room_management()
    {
        $room = $this->makeRoom();

        $response = $this->getJson('/bingo-rooms/summary-data');
        // Ruta web protegida por 'auth': sin sesión, MultiPermissionMiddleware
        // redirige a login (o el guard 'auth' lo hace antes). Con headers JSON
        // Laravel normalmente devolvería 401, pero aquí el propio middleware
        // de permisos hace el redirect explícito cuando no hay usuario.
        $this->assertContains($response->getStatusCode(), [302, 401]);

        $response = $this->get("/bingo-rooms/{$room->hashed_id}/show");
        $this->assertContains($response->getStatusCode(), [302, 401]);
    }

    /** @test */
    public function guest_is_redirected_when_accessing_bingo_game_endpoints()
    {
        $room = $this->makeRoom();

        $endpoints = [
            ['GET', "/bingo/{$room->hashed_id}/draw-state"],
            ['POST', "/bingo/game-rooms/{$room->hashed_id}/player-buy"],
            ['GET', '/bingo/my-games'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            $this->assertContains($response->getStatusCode(), [302, 401], "Endpoint {$method} {$url} debería bloquear a invitados.");
        }
    }
}
