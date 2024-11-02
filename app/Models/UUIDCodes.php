<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UUIDCodes extends Model
{
    protected $table = "uuid_codes";
    protected $fillable = ['code', 'user_type', 'status'];

    public function UserType()
    {
        return $this->belongsTo(UserType::class, 'user_type');
    }

    public static function existsCode($code)
    {
        return self::where('code', $code)->exists();
    }
}
