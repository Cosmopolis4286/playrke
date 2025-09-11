<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoImage extends Model
{
    protected $table = 'bingo_images';
    protected $fillable = ['type', 'param', 'route', 'code', 'status'];
}
