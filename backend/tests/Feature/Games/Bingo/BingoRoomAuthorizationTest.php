<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Policies\BingoRoomPolicy;
use App\Models\User;
use App\Services\Auth\RoleAuthorizationService;
use Mockery;
use Tests\TestCase;

class BingoRoomAuthorizationTest extends TestCase
{
    public function test_business_role_alone_does_not_grant_access_to_another_rooms_game_state_or_lobby_players(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = '960000000000000002';
        $user->shouldReceive('hasRole')->with('player')->andReturn(false);

        $room = new BingoRoom();
        $room->id = '960000000000000100';
        $room->creator_user_id = '960000000000000001';

        $roles = Mockery::mock(RoleAuthorizationService::class);
        $roles->shouldReceive('hasAnyRole')->andReturnUsing(
            static fn ($candidate, array $requiredRoles): bool => !in_array('admin', $requiredRoles, true)
                && !in_array('superadmin', $requiredRoles, true)
        );
        $this->app->instance(RoleAuthorizationService::class, $roles);

        $policy = app(BingoRoomPolicy::class);

        $this->assertFalse($policy->viewGame($user, $room));
        $this->assertFalse($policy->viewLobbyPlayers($user, $room));
    }

    public function test_room_creator_can_view_their_own_game_and_lobby_players(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = '960000000000000001';

        $room = new BingoRoom();
        $room->id = '960000000000000100';
        $room->creator_user_id = '960000000000000001';

        $roles = Mockery::mock(RoleAuthorizationService::class);
        $roles->shouldReceive('hasAnyRole')->andReturn(false);
        $this->app->instance(RoleAuthorizationService::class, $roles);

        $policy = app(BingoRoomPolicy::class);

        $this->assertTrue($policy->viewGame($user, $room));
        $this->assertTrue($policy->viewLobbyPlayers($user, $room));
    }
}
