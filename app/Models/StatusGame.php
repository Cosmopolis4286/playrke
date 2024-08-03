<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusGame extends Model
{
    protected $table = "game_status";
    protected $fillable = ['id', 'info'];

    public function Status()
    {
        return $this->hasMany(BingoOffRoom::class, 'id');
    }
}
