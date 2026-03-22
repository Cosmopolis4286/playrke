<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaMultiQuest extends Model
{
    protected $table = "trivia_multi_quest";
    protected $fillable = [
        'room',
        'num_quest',
        'imagen',
        'question',
        'answer',
        'option_1',
        'option_2',
        'option_3'
    ];

    public function Room()
    {
        return $this->belongsTo(TriviaMulti::class, 'room');
    }
}
