<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\ExternalPlayerParticipation;
use App\Models\Roles\Player\External\UserPurchaseCode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cobertura de la vista pública de juego para un ExternalPlayer (comprador
 * de cartones sin cuenta, ver App\Http\Controllers\ExternalPlayerGameController)
 * — mismo patrón de fixtures que Tests\Feature\Api\BingoTest (inserts
 * directos con FOREIGN_KEY_CHECKS=0, sin depender del flujo HTTP completo
 * de venta del vendedor).
 */
class ExternalPlayerGameTest extends TestCase
{
    use DatabaseTransactions;

    protected User $creator;
    protected Currency $currency;
    protected Country $country;

    private static int $idCounter = 0;

    private function nextId(): string
    {
        return (string) (300000000000000000 + (++self::$idCounter));
    }

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        GameStatus::forgetCache();
        foreach (['ACTIVE', 'IN_PROGRESS', 'FINISHED'] as $code) {
            if (!GameStatus::where('code', $code)->exists()) {
                GameStatus::create(['code' => $code, 'label' => $code]);
            }
        }

        $this->currency = Currency::firstOrCreate(
            ['iso_code' => 'USD'],
            ['symbol' => '$', 'name' => 'US Dollar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE']
        );

        $this->country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO', 'status' => 'ACTIVE']
        );

        \App\Models\Game::firstOrCreate(['name' => 'BINGO'], ['status' => 'ACTIVE']);

        $this->creator = User::factory()->create(['country_id' => $this->country->id]);
    }

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    private function makeRoom(string $statusCode = 'ACTIVE'): BingoRoom
    {
        $roomId = $this->nextId();

        DB::table('bingo_rooms')->insert([
            'id' => $roomId,
            'uuid_code' => (string) Str::uuid(),
            'status_id' => GameStatus::codeToId($statusCode),
            'creator_user_id' => $this->creator->id,
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
        ]);

        DB::table('bingo_rooms_config')->insert([
            'id' => $this->nextId(),
            'room_id' => $roomId,
            'template_id' => null,
            'parent_automatic_id' => null,
            'is_scheduled' => 'N',
            'from_house' => 'N',
            'room_type_id' => $this->nextId(),
            'creator_user_id' => $this->creator->id,
            'connection_type_id' => $this->nextId(),
            'in_live' => 'N',
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
        ]);

        return BingoRoom::find($roomId);
    }

    private function makeSharedRoomUser(BingoRoom $room): BingoRoomUser
    {
        return BingoRoomUser::create([
            'room_id' => $room->id,
            'user_id' => $this->creator->id,
            'role' => BingoRoomUser::ROLE_PLAYER,
            'cards_purchased' => 0,
            'winner' => 'N',
        ]);
    }

    private function makeExternalPlayer(BingoRoom $room, BingoRoomUser $roomUser, string $phone): ExternalPlayer
    {
        $purchaseCodeId = UserPurchaseCode::create([
            'first_name' => 'Test',
            'last_name'  => 'Player',
            'country_id' => $this->country->id,
            'phone'      => $phone,
            'purchase_code' => strtoupper(Str::random(8)),
            'status'     => 'ACTIVE',
        ])->id;

        return ExternalPlayer::create([
            'game_id'          => \App\Models\Game::getValueByName('BINGO', 'id'),
            'room_id'          => (string) $room->id,
            'creator_user_id'  => (string) $this->creator->id,
            'seller_user_id'   => (string) $this->creator->id,
            'room_user_id'     => (string) $roomUser->id,
            'link'             => Str::random(28),
            'expiration_date'  => now()->addDays(3),
            'country_id'       => (string) $this->country->id,
            'currency_id'      => (string) $this->currency->id,
            'first_name'       => 'Test',
            'last_name'        => 'Player',
            'identification'   => null,
            'phone'            => $phone,
            'purchase_code_id' => (string) $purchaseCodeId,
            'status'           => 'ACTIVE',
        ]);
    }

    private function makeCardForPlayer(BingoRoom $room, BingoRoomUser $roomUser, ExternalPlayer $player): BingoCard
    {
        $card = BingoCard::create([
            'id' => BingoCard::generateSnowflakeId(),
            'room_id' => $room->id,
            'creator_user_id' => $this->creator->id,
            'room_user_id' => $roomUser->id,
            'card_lot_id' => $this->nextId(),
            'currency_id' => $this->currency->id,
            'serial_number' => 'BINGO-' . strtoupper(substr((string) Str::uuid(), 0, 8)),
            'card_numbers' => json_encode(['B' => [1, 2, 3, 4, 5]]),
            'card_hash' => hash('sha256', uniqid('', true)),
            'status' => BingoCard::STATUS_ACTIVE,
            'is_validated' => false,
            'is_custom_card' => false,
        ]);

        ExternalPlayerParticipation::recordFor($player->id, BingoCard::class, $card->id);

        return $card;
    }

    // ─── Acceso público ─────────────────────────────────────────────────────

    /** @test */
    public function external_player_can_view_the_game_page_without_authentication(): void
    {
        $room = $this->makeRoom();
        $roomUser = $this->makeSharedRoomUser($room);
        $player = $this->makeExternalPlayer($room, $roomUser, '+1000000001');
        $this->makeCardForPlayer($room, $roomUser, $player);

        $response = $this->get("/bingo/external/{$player->link}");

        $response->assertOk();
    }

    /** @test */
    public function invalid_link_returns_404(): void
    {
        $response = $this->get('/bingo/external/this-link-does-not-exist-000000');

        $response->assertStatus(404);
    }

    /** @test */
    public function expired_link_is_rejected(): void
    {
        $room = $this->makeRoom();
        $roomUser = $this->makeSharedRoomUser($room);
        $player = $this->makeExternalPlayer($room, $roomUser, '+1000000002');
        $player->update(['expiration_date' => now()->subDay()]);

        $response = $this->get("/bingo/external/{$player->link}");

        $response->assertStatus(410);
    }

    /** @test */
    public function draw_state_is_accessible_without_authentication(): void
    {
        $room = $this->makeRoom();
        $roomUser = $this->makeSharedRoomUser($room);
        $player = $this->makeExternalPlayer($room, $roomUser, '+1000000003');

        $response = $this->getJson("/bingo/external/{$player->link}/draw-state");

        $response->assertOk();
    }

    // ─── Separación de cartones por jugador (el bug que motivó esta feature) ──

    /** @test */
    public function external_player_only_sees_their_own_cards_not_other_buyers_from_same_seller(): void
    {
        $room = $this->makeRoom();
        // Ambos compradores comparten el MISMO BingoRoomUser (mismo vendedor,
        // misma sala) — exactamente el escenario que hacía imposible separar
        // cartones antes de ExternalPlayerParticipation.
        $roomUser = $this->makeSharedRoomUser($room);

        $playerA = $this->makeExternalPlayer($room, $roomUser, '+1000000004');
        $playerB = $this->makeExternalPlayer($room, $roomUser, '+1000000005');

        $cardA = $this->makeCardForPlayer($room, $roomUser, $playerA);
        $this->makeCardForPlayer($room, $roomUser, $playerA);
        $cardB = $this->makeCardForPlayer($room, $roomUser, $playerB);

        $responseA = $this->getJson("/bingo/external/{$playerA->link}/user-cards");
        $responseA->assertOk();
        $cardsA = collect($responseA->json('data'))->pluck('id')->map(fn ($id) => (string) $id);

        $this->assertCount(2, $cardsA);
        $this->assertTrue($cardsA->contains((string) $cardA->id));
        $this->assertFalse($cardsA->contains((string) $cardB->id));

        $responseB = $this->getJson("/bingo/external/{$playerB->link}/user-cards");
        $responseB->assertOk();
        $cardsB = collect($responseB->json('data'))->pluck('id')->map(fn ($id) => (string) $id);

        $this->assertCount(1, $cardsB);
        $this->assertTrue($cardsB->contains((string) $cardB->id));
    }
}
