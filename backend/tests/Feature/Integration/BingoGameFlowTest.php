<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Models\BingoRoom;
use App\Models\BingoDraw;
use App\Models\Wallet;
use App\Models\Currency;
use App\Domains\Games\Bingo\Events\BingoNumberCalled;
use App\Domains\Games\Bingo\Events\BingoGameStarted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BingoGameFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_bingo_game_flow()
    {
        Event::fake();

        // 1. Create a player
        $player = User::factory()->create([
            'username' => 'player1',
            'email' => 'player1@test.com',
        ]);

        // 2. Create currency and wallet
        $currency = Currency::factory()->create(['code' => 'USD']);

        $wallet = Wallet::factory()->create([
            'user_id' => $player->id,
            'currency_id' => $currency->id,
            'balance' => 1000,
        ]);

        // 3. Create a bingo room
        $room = BingoRoom::factory()->create([
            'name' => 'Test Room',
            'card_price' => 50,
            'max_players' => 10,
            'prize_pool' => 500,
            'status' => 'waiting',
            'currency_id' => $currency->id,
        ]);

        // 4. Player views available rooms
        $response = $this->actingAs($player)
            ->getJson('/api/bingo/rooms');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Test Room']);

        // 5. Player purchases cards
        $response = $this->actingAs($player)
            ->postJson("/api/bingo/rooms/{$room->id}/purchase", [
                'quantity' => 3,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'total_cost' => 150,
                'new_balance' => 850,
            ]);

        // Verify wallet was debited
        $this->assertEquals(850, $wallet->fresh()->balance);

        // 6. Player gets their cards
        $response = $this->actingAs($player)
            ->getJson("/api/bingo/rooms/{$room->id}/cards");

        $response->assertOk()
            ->assertJsonCount(3, 'cards');

        $cards = $response->json('cards');

        // Verify each card has 5x5 grid
        foreach ($cards as $card) {
            $this->assertCount(5, $card['numbers']); // 5 columns
            foreach ($card['numbers'] as $column) {
                $this->assertCount(5, $column); // 5 rows each
            }
        }

        // 7. Game starts
        $draw = BingoDraw::factory()->create([
            'room_id' => $room->id,
            'status' => 'active',
            'current_number' => null,
            'called_numbers' => json_encode([]),
        ]);

        $room->update(['status' => 'playing']);

        // Broadcast game started event
        broadcast(new BingoGameStarted($room->id, $draw->id));

        Event::assertDispatched(BingoGameStarted::class);

        // 8. Player checks current draw
        $response = $this->actingAs($player)
            ->getJson("/api/bingo/rooms/{$room->id}/draw");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'draw' => [
                    'id' => $draw->id,
                    'status' => 'active',
                ],
            ]);

        // 9. Numbers are called
        $calledNumbers = [];
        for ($i = 1; $i <= 5; $i++) {
            $number = $i * 10;
            $calledNumbers[] = $number;

            $draw->update([
                'current_number' => $number,
                'called_numbers' => json_encode($calledNumbers),
            ]);

            broadcast(new BingoNumberCalled($number, $room->id));
        }

        Event::assertDispatched(BingoNumberCalled::class, 5);

        // 10. Player checks called numbers
        $response = $this->actingAs($player)
            ->getJson("/api/bingo/draw/{$draw->id}/numbers");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'called_numbers' => $calledNumbers,
                'current_number' => 50,
            ]);

        // 11. Player checks for winner
        $card = $cards[0];
        $response = $this->actingAs($player)
            ->postJson("/api/bingo/cards/{$card['id']}/check-winner");

        $response->assertOk()
            ->assertJsonStructure(['success', 'is_winner']);

        // 12. Player views game history
        $response = $this->actingAs($player)
            ->getJson('/api/bingo/history');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'history',
                'pagination',
            ]);
    }

    /** @test */
    public function multiple_players_can_join_same_room()
    {
        $currency = Currency::factory()->create(['code' => 'USD']);

        $room = BingoRoom::factory()->create([
            'card_price' => 10,
            'currency_id' => $currency->id,
        ]);

        $players = User::factory()->count(3)->create();

        foreach ($players as $player) {
            Wallet::factory()->create([
                'user_id' => $player->id,
                'currency_id' => $currency->id,
                'balance' => 100,
            ]);

            $response = $this->actingAs($player)
                ->postJson("/api/bingo/rooms/{$room->id}/purchase", [
                    'quantity' => 1,
                ]);

            $response->assertOk();
        }

        // Verify all players have cards
        foreach ($players as $player) {
            $response = $this->actingAs($player)
                ->getJson("/api/bingo/rooms/{$room->id}/cards");

            $response->assertOk()
                ->assertJsonCount(1, 'cards');
        }
    }

    /** @test */
    public function player_cannot_purchase_cards_from_different_currency_room()
    {
        $usdCurrency = Currency::factory()->create(['code' => 'USD']);
        $vesCurrency = Currency::factory()->create(['code' => 'VES']);

        $player = User::factory()->create();

        // Player has USD wallet
        Wallet::factory()->create([
            'user_id' => $player->id,
            'currency_id' => $usdCurrency->id,
            'balance' => 100,
        ]);

        // Room accepts VES
        $room = BingoRoom::factory()->create([
            'card_price' => 10,
            'currency_id' => $vesCurrency->id,
        ]);

        $response = $this->actingAs($player)
            ->postJson("/api/bingo/rooms/{$room->id}/purchase", [
                'quantity' => 1,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Billetera no encontrada',
            ]);
    }

    /** @test */
    public function player_can_filter_rooms_by_currency()
    {
        $usdCurrency = Currency::factory()->create(['code' => 'USD', 'id' => 1]);
        $vesCurrency = Currency::factory()->create(['code' => 'VES', 'id' => 2]);

        BingoRoom::factory()->count(2)->create(['currency_id' => $usdCurrency->id]);
        BingoRoom::factory()->count(3)->create(['currency_id' => $vesCurrency->id]);

        $player = User::factory()->create();

        $response = $this->actingAs($player)
            ->getJson('/api/bingo/rooms?currency=1');

        $response->assertOk()
            ->assertJsonCount(2, 'rooms');

        $response = $this->actingAs($player)
            ->getJson('/api/bingo/rooms?currency=2');

        $response->assertOk()
            ->assertJsonCount(3, 'rooms');
    }

    /** @test */
    public function complete_authentication_and_purchase_flow()
    {
        // 1. Register new user
        $response = $this->postJson('/api/auth/register', [
            'username' => 'newplayer',
            'email' => 'newplayer@test.com',
            'first_name' => 'New',
            'last_name' => 'Player',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country_id' => 1,
            'terms' => true,
        ]);

        $response->assertStatus(201);

        $userId = $response->json('user.id');

        // 2. Logout
        $response = $this->postJson('/api/auth/logout');
        $response->assertOk();

        // 3. Login
        $response = $this->postJson('/api/auth/login', [
            'login' => 'newplayer',
            'password' => 'password123',
        ]);

        $response->assertOk();

        $user = User::find($userId);

        // 4. Get user profile
        $response = $this->actingAs($user)
            ->getJson('/api/user/profile');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'profile' => [
                    'username' => 'newplayer',
                    'email' => 'newplayer@test.com',
                ],
            ]);

        // 5. Update preferences
        $response = $this->actingAs($user)
            ->putJson('/api/user/preferences', [
                'theme' => 'dark',
                'language' => 'es',
            ]);

        $response->assertOk();

        // 6. Create wallet and make purchase
        $currency = Currency::factory()->create(['code' => 'USD']);

        Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 500,
        ]);

        $room = BingoRoom::factory()->create([
            'card_price' => 25,
            'currency_id' => $currency->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/bingo/rooms/{$room->id}/purchase", [
                'quantity' => 2,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'total_cost' => 50,
                'new_balance' => 450,
            ]);
    }
}
