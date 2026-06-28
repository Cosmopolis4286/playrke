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

// Public Bingo Lobby Channel (no authentication required)
// Notifica activación/desactivación de salas para la vista de game-rooms.
Broadcast::channel('bingo.lobby', function () {
    return true; // Public channel - anyone can listen
});

// Canal público del feed de ganancias en vivo (toda la plataforma, cualquier juego/premio).
Broadcast::channel('platform.win-events', function () {
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

// Canal privado para admins/superadmins: actividad general del sistema (altas de usuario, ventas)
Broadcast::channel('admins.activity', function ($user) {
    return $user->hasRole(['admin', 'superadmin']);
});

// Canal privado de actividad propia de un seller/commerce (ventas de cartones de sus salas)
Broadcast::channel('creators.activity.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId || $user->hasRole(['admin', 'superadmin']);
});

// Canal privado de resultados individuales de Trivia (solo el propio jugador)
Broadcast::channel('trivia.player.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
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

// Presence Channel global: cualquier usuario autenticado se une al cargar la SPA,
// permite que los dashboards de Admin/SuperAdmin cuenten usuarios en línea en vivo.
Broadcast::channel('online-users', function ($user) {
    return [
        'id'       => (string) $user->id,
        'username' => $user->username,
    ];
});