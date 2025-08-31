<?php

namespace App\Models\User\Shared;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class UUIDCodes extends Model
{
    protected $table = "uuid_codes";
    protected $fillable = ['code_uuid', 'user_id', 'status'];

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getByUser($userId, $fields = ['*'])
    {
        return self::select($fields)->where('user_id', $userId)->first();
    }

    public static function getByCode($codeUUID, $fields = ['*'])
    {
        return self::select($fields)->where('code_uuid', $codeUUID)->first();
    }

    public static function getValueByUser($userId, $value)
    {
        return self::where('user_id', $userId)->value($value);
    }

    public static function existsCode($code)
    {
        return self::where('code_uuid', $code)->exists();
    }
}
