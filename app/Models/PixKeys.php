<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PixKeys extends Model
{
    protected $table = "pix_keys";
    protected $fillable = ['value'];
}
