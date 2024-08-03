<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaMultiQuest extends Model
{
    protected $table = "triv_multi_quest";
    protected $fillable = [
        'id', 'room', 'num_quest', 'send', 'question', 'imagen',
        'correct_answer', 'option_1', 'option_2', 'option_3'
    ];

    public function Room()
    {
        return $this->belongsTo(TriviaMulti::class, 'room');
    }
}
