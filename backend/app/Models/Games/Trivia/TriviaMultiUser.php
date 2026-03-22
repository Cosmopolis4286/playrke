<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaMultiUser extends Model
{
    protected $table = "trivia_multi_user";
    protected $fillable = [
        'user', 'room', 'c_half', 'c_half_use', 'c_jump',
        'start', 'lose_win', 'num_quest', 'init_gm', 'fin_qt', 'end_gm'
    ];

    public function Room()
    {
        return $this->belongsTo(TriviaMulti::class, 'room');
    }

    public function PlayersRoom()
    {
        return $this->belongsTo(User::class, 'user');
    }
}
