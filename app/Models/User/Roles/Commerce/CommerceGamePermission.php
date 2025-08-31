<?php

namespace App\Models\User\Roles\Commerce;

use Illuminate\Database\Eloquent\Model;

class CommerceGamePermission extends Model
{
    protected $table = "commerce_game_permission";
    protected $fillable = ['commerce_id', 'game', 'status'];

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }
}
