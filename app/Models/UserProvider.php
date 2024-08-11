<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProvider extends Model
{
    protected $table = "users_providers";
    protected $fillable = ['user', 'country', 'provider', 'last_activity'];
}
