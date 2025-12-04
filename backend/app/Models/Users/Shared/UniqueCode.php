<?php

namespace App\Models\Users\Shared;

use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para códigos únicos del sistema.
 */
class UniqueCode extends Model
{
    use SnowflakeId;

    protected $table = "unique_codes";

    protected $fillable = [
        'rke_code',
        'uuid_code',
        'user_id',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

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
