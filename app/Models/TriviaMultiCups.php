<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaMultiCups extends Model
{

    protected $table = "triv_multi_cups";
    protected $fillable = ['room', 'num_quest', 'cups'];

    public function Room()
    {
        return $this->belongsTo(TriviaMulti::class, 'room');
    }
}
