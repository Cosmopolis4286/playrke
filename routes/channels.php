<?php

use App\Models\BingoRoom;
use App\Models\PlayerLink;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('bingocash.{encrypted}', function ($encrypted) {
    // Verifica si el valor de playerLink existe en tu base de datos
    return BingoRoom::where('encrypted', $encrypted)->exists();
});
