<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaPlayers extends Model
{
    protected $table = 'polla_players';
    protected $fillable = [
        'draw',
        'player_type',
        'player_id',
        'player_name',
        'player_lastname',
        'identification_doc',
        'plays'
    ];

    public function Draw()
    {
        return $this->belongsTo(PollaDraw::class, 'draw');
    }
}
