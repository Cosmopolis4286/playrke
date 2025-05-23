<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommerceGamePermission extends Model
{
    protected $table = "commerce_game_permission";
    protected $fillable = ['commerce', 'game', 'status'];

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }
}
