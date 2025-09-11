<?php

namespace App\Models\Games\Shared\PrizePool;

use Illuminate\Database\Eloquent\Model;

class PrizePoolType extends Model
{
    protected $table = 'prize_pool_types';

    protected $fillable = [
        'description',
    ];

    /* 
     | Aquí solo irían relaciones si en el futuro
     | PrizePoolType pertenece a otra entidad 
     | (ej: GamePrizePoolType).
     */
}
