<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\BingoRoom;
use App\Models\BingoCtns;
use App\Models\BingoDraw;
use App\Models\Wallet;
use App\Models\Currencies\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BingoTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->currency = Currency::factory()->create(['code' => 'USD']);
    }

    /** @test */
    public function user_can_get_list_of_rooms()
    {
        BingoRoom::factory()->count(3)->create([
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/bingo/rooms');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'rooms',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(3, 'rooms');
    }

    /** @test */
    public function user_can_filter_rooms_by_status()
    {
        BingoRoom::factory()->create([
            'status' => 'active',
            'currency_id' => $this->currency->id,
        ]);
        BingoRoom::factory()->create([
            'status' => 'waiting',
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/bingo/rooms?status=active');

        $response->assertOk()
            ->assertJsonCount(1, 'rooms');
    }

    /** @test */
    public function user_can_get_single_room_details()
    {
        $room = BingoRoom::factory()->create([
            'name' => 'Test Room',
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/bingo/rooms/{$room->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'room' => [
                    'id' => $room->id,
                    'name' => 'Test Room',
                ],
            ]);
    }

    /** @test */
    public function user_can_purchase_bingo_cards()
    {
        $room = BingoRoom::factory()->create([
            'card_price' => 10,
            'currency_id' => $this->currency->id,
        ]);

        // Create wallet with sufficient balance
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'balance' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/bingo/rooms/{$room->id}/purchase", [
                'quantity' => 2,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Cartones comprados exitosamente',
                'total_cost' => 20,
            ])
            ->assertJsonStructure([
                'cards' => [
                    '*' => ['id', 'numbers']
                ],
                'new_balance',
            ]);

        // Verify wallet was debited
        $this->assertEquals(80, $wallet->fresh()->balance);

        // Verify cards were created
        $this->assertCount(2, BingoCtns::where('user_id', $this->user->id)->get());
    }

    /** @test */
    public function user_cannot_purchase_cards_with_insufficient_balance()
    {
        $room = BingoRoom::factory()->create([
            'card_price' => 100,
            'currency_id' => $this->currency->id,
        ]);

        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'balance' => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/bingo/rooms/{$room->id}/purchase", [
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Saldo insuficiente',
            ]);
    }

    /** @test */
    public function user_can_get_their_cards_for_a_room()
    {
        $room = BingoRoom::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        // Create some cards for the user
        BingoCtns::factory()->count(3)->create([
            'room_id' => $room->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/bingo/rooms/{$room->id}/cards");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'cards');
    }

    /** @test */
    public function user_can_get_current_draw()
    {
        $room = BingoRoom::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $draw = BingoDraw::factory()->create([
            'room_id' => $room->id,
            'status' => 'active',
            'current_number' => 42,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/bingo/rooms/{$room->id}/draw");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'draw' => [
                    'id' => $draw->id,
                    'current_number' => 42,
                    'status' => 'active',
                ],
            ]);
    }

    /** @test */
    public function returns_404_when_no_active_draw()
    {
        $room = BingoRoom::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/bingo/rooms/{$room->id}/draw");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No hay sorteo activo',
            ]);
    }

    /** @test */
    public function user_can_get_called_numbers()
    {
        $draw = BingoDraw::factory()->create([
            'called_numbers' => json_encode([1, 5, 12, 23, 42]),
            'current_number' => 42,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/bingo/draw/{$draw->id}/numbers");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'called_numbers' => [1, 5, 12, 23, 42],
                'current_number' => 42,
            ]);
    }

    /** @test */
    public function user_can_check_if_card_is_winner()
    {
        $card = BingoCtns::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/bingo/cards/{$card->id}/check-winner");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure(['is_winner']);
    }

    /** @test */
    public function user_cannot_check_winner_for_other_users_card()
    {
        $otherUser = User::factory()->create();

        $card = BingoCtns::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/bingo/cards/{$card->id}/check-winner");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Este cartón no te pertenece',
            ]);
    }

    /** @test */
    public function user_can_get_winners_for_a_room()
    {
        $room = BingoRoom::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/bingo/rooms/{$room->id}/winners");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure(['winners']);
    }

    /** @test */
    public function user_can_get_their_game_history()
    {
        // Create some play history
        \App\Models\PlayHistory::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/bingo/history');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'history',
                'pagination',
            ]);
    }

    /** @test */
    public function user_can_filter_history_by_date_range()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/bingo/history?from=2025-01-01&to=2025-12-31');

        $response->assertOk();
    }

    /** @test */
    public function quantity_must_be_between_1_and_10()
    {
        $room = BingoRoom::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'balance' => 1000,
        ]);

        // Test 0 cards
        $response = $this->actingAs($this->user)
            ->postJson("/api/bingo/rooms/{$room->id}/purchase", [
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        // Test 11 cards
        $response = $this->actingAs($this->user)
            ->postJson("/api/bingo/rooms/{$room->id}/purchase", [
                'quantity' => 11,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /** @test */
    public function guest_cannot_access_bingo_endpoints()
    {
        $room = BingoRoom::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $endpoints = [
            ['GET', '/api/bingo/rooms'],
            ['GET', "/api/bingo/rooms/{$room->id}"],
            ['POST', "/api/bingo/rooms/{$room->id}/purchase"],
            ['GET', '/api/bingo/history'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            $response->assertStatus(401);
        }
    }
}
