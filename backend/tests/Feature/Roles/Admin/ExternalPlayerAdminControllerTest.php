<?php

namespace Tests\Feature\Roles\Admin;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Role;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\UserPurchaseCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalPlayerAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    protected function setUp() : void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_admin_can_block_and_unblock_external_player() : void
    {
        $superadmin = $this->makeSuperadmin();
        $externalPlayer = $this->makeExternalPlayer('ACTIVE');

        $block = $this->actingAs($superadmin)->putJson("/admin/external-players/{$externalPlayer->id}/toggle-block");
        $block->assertOk();
        $this->assertSame('BLOCKED', $externalPlayer->fresh()->status);

        $unblock = $this->actingAs($superadmin)->putJson("/admin/external-players/{$externalPlayer->id}/toggle-block");
        $unblock->assertOk();
        $this->assertSame('ACTIVE', $externalPlayer->fresh()->status);
    }

    public function test_admin_can_export_players_as_csv() : void
    {
        $superadmin = $this->makeSuperadmin();
        $this->makeExternalPlayer('ACTIVE');
        $this->makeExternalPlayer('BLOCKED');

        $response = $this->actingAs($superadmin)->get('/admin/external-players/export');

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Nombre,Apellido,Teléfono', $content);
        $this->assertSame(3, substr_count($content, "\n")); // header + 2 filas
    }

    public function test_admin_can_list_external_players() : void
    {
        $superadmin = $this->makeSuperadmin();
        $this->makeExternalPlayer();

        $response = $this->actingAs($superadmin)->getJson('/admin/external-players/fetch-list');

        $response->assertOk()->assertJsonCount(1, 'players.data');
        $response->assertJsonPath('statistics.total', 1);
        $response->assertJsonPath('players.total', 1);
        $response->assertJsonPath('players.per_page', 20);
    }

    public function test_export_respects_status_filter() : void
    {
        $superadmin = $this->makeSuperadmin();
        $this->makeExternalPlayer('ACTIVE');
        $this->makeExternalPlayer('BLOCKED');

        $response = $this->actingAs($superadmin)->get('/admin/external-players/export?status=BLOCKED');

        $content = $response->streamedContent();
        $this->assertSame(2, substr_count($content, "\n")); // header + 1 fila
    }

    public function test_guest_is_redirected_to_login() : void
    {
        $this->getJson('/admin/external-players/fetch-list')->assertStatus(401);
    }

    public function test_index_exposes_available_games_for_filter() : void
    {
        $superadmin = $this->makeSuperadmin();
        Game::create(['name' => 'TRIVIA', 'status' => 'ACTIVE']);
        Game::create(['name' => 'BINGO', 'status' => 'INACTIVE']); // no debe listarse

        $response = $this->actingAs($superadmin)->get('/admin/external-players');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Roles/Admin/ExternalPlayer/Index')
                ->has('games', 1)
                ->where('games.0.name', 'TRIVIA'),
        );
    }

    public function test_list_is_paginated() : void
    {
        $superadmin = $this->makeSuperadmin();
        for ($i = 0; $i < 3; $i++) {
            $this->makeExternalPlayer();
        }

        $response = $this->actingAs($superadmin)->getJson('/admin/external-players/fetch-list?per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'players.data');
        $response->assertJsonPath('players.total', 3);
        $response->assertJsonPath('players.last_page', 2);
    }

    public function test_non_admin_cannot_access_external_players_routes() : void
    {
        $player = $this->makePlayer();
        $externalPlayer = $this->makeExternalPlayer();

        $this->actingAs($player)->getJson('/admin/external-players/fetch-list')->assertStatus(403);
        $this->actingAs($player)->putJson("/admin/external-players/{$externalPlayer->id}/toggle-block")->assertStatus(403);
    }

    public function test_non_admin_cannot_export() : void
    {
        $player = $this->makePlayer();

        // getJson (no get): el proyecto usa RoleMiddleware, que redirige en
        // requests no-JSON — mismo patrón que el resto de tests de autorización
        // (ver PermissionControllerTest, ModuleControllerTest).
        $this->actingAs($player)->getJson('/admin/external-players/export')->assertStatus(403);
    }

    public function test_repository_generates_unique_28_char_link() : void
    {
        $repository = app(\App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface::class);

        $link = $repository->generateUniqueLink();

        $this->assertSame(28, strlen($link));
        $this->assertFalse($repository->existsByLink($link));
    }

    public function test_search_filters_by_seller() : void
    {
        $superadmin = $this->makeSuperadmin();
        $playerA = $this->makeExternalPlayer();
        $this->makeExternalPlayer();

        $response = $this->actingAs($superadmin)
            ->getJson('/admin/external-players/fetch-list?seller_user_id=' . $playerA->seller_user_id);

        $response->assertOk()->assertJsonCount(1, 'players.data');
    }

    public function test_search_filters_by_status() : void
    {
        $superadmin = $this->makeSuperadmin();
        $this->makeExternalPlayer('ACTIVE');
        $this->makeExternalPlayer('BLOCKED');

        $response = $this->actingAs($superadmin)->getJson('/admin/external-players/fetch-list?status=BLOCKED');

        $response->assertOk()->assertJsonCount(1, 'players.data');
    }

    public function test_statistics_reflect_all_statuses_in_one_pass() : void
    {
        $superadmin = $this->makeSuperadmin();
        $this->makeExternalPlayer('ACTIVE');
        $this->makeExternalPlayer('INACTIVE');
        $this->makeExternalPlayer('BLOCKED');

        $response = $this->actingAs($superadmin)->getJson('/admin/external-players/fetch-list');

        $response->assertOk();
        $response->assertJsonPath('statistics.total', 3);
        $response->assertJsonPath('statistics.active', 1);
        $response->assertJsonPath('statistics.inactive', 1);
        $response->assertJsonPath('statistics.blocked', 1);
    }

    private function makeExternalPlayer(string $status = 'ACTIVE') : ExternalPlayer
    {
        // Country factory usa códigos aleatorios de 2/3 letras; en suites largas
        // pueden colisionar con países ya sembrados y hacer flaky el test.
        do {
            $iso2 = strtoupper(\Illuminate\Support\Str::random(2));
        } while (Country::where('iso_code_2', $iso2)->exists());

        do {
            $iso3 = strtoupper(\Illuminate\Support\Str::random(3));
        } while (Country::where('iso_code_3', $iso3)->exists());

        $country = Country::factory()->create([
            'iso_code_2' => $iso2,
            'iso_code_3' => $iso3,
        ]);
        $currency = Currency::factory()->create();
        $game = Game::create(['name' => 'Bingo Demo ' . $this->nextId(), 'status' => 'ACTIVE']);
        $creator = User::factory()->create();
        $seller = User::factory()->create();

        $purchaseCode = UserPurchaseCode::create([
            'first_name' => 'Ana',
            'last_name' => 'Externa',
            'country_id' => $country->id,
            'phone' => '+58 412 0000000',
            'purchase_code' => strtoupper(\Illuminate\Support\Str::random(8)),
            'status' => 'ACTIVE',
            'expiration_date' => now()->addDays(7),
        ]);

        return ExternalPlayer::create([
            'game_id' => $game->id,
            'room_id' => (string) $game->id,
            'creator_user_id' => $creator->id,
            'seller_user_id' => $seller->id,
            'room_user_id' => (string) \Illuminate\Support\Str::random(10),
            'link' => \Illuminate\Support\Str::random(28),
            'expiration_date' => now()->addDays(7),
            'country_id' => $country->id,
            'currency_id' => $currency->id,
            'first_name' => 'Ana',
            'last_name' => 'Externa',
            'identification' => null,
            'phone' => '+58 412 0000000',
            'purchase_code_id' => $purchaseCode->id,
            'status' => $status,
        ]);
    }

    private function makePlayer() : User
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        return $user;
    }

    private function makeSuperadmin() : User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    private function nextId() : string
    {
        return (string) (600000000000000000 + (++self::$idCounter));
    }

    private function seedRoles() : void
    {
        foreach (['superadmin', 'admin', 'player'] as $name) {
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                $role = new Role([
                    'name' => $name,
                    'guard_name' => 'web',
                    'prefix' => $name,
                    'type' => 'web',
                    'status' => true,
                ]);
                $role->id = $this->nextId();
                $role->save();
            }
        }
    }
}
