<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Private User Channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public Bingo Room Channels (no authentication required)
Broadcast::channel('bingo.room.{roomId}', function () {
    return true; // Public channel - anyone can listen
});

// Private Bingo Player Channel (authenticated users only)
Broadcast::channel('bingo.player.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal privado para admins/superadmins: movimientos de billetera pendientes
Broadcast::channel('admins.wallet-movements', function ($user) {
    return $user->hasRole(['admin', 'superadmin']);
});

// Presence Channel for Bingo Room (shows who's online)
Broadcast::channel('bingo.room.{roomId}.presence', function ($user, $roomId) {
    // Return user info if they can join this room
    return [
        'id' => $user->id,
        'name' => $user->first_name . ' ' . $user->last_name,
        'username' => $user->username,
    ];
});