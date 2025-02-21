<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtrasUser extends Model
{
    protected $table = "extras_user";
    protected $fillable = ['id', 'user', 'lives', 'c_special', 'c_jump', 'c_half', 'auto_m'];

    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }
}
