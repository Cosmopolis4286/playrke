<?php

namespace App\Models\Users\Shared;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;

class UniqueCode extends Model
{
    protected $table = "unique_codes";
    protected $fillable = ['rke_code', 'uuid_code', 'user_id', 'status'];

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getByUser($userId, $fields = ['*'])
    {
        return self::select($fields)->where('user_id', $userId)->first();
    }

    public static function getValueByUser($userId, $value)
    {
        return self::where('user_id', $userId)->value($value);
    }

    public static function getByCode($codeUUID, $fields = ['*'])
    {
        return self::select($fields)->where('uuid_code', $codeUUID)->first();
    }

    public static function existsCode($code)
    {
        return self::where('uuid_code', $code)->exists();
    }

    public static function getByRKECode($code, $fields = ['*'])
    {
        return self::select($fields)->where('rke_code', $code)->first();
    }

    public static function existsRKECode($code)
    {
        return self::where('rke_code', $code)->exists();
    }
}
