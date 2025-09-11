<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $table = 'rewards';
    protected $fillable = ['code', 'reward_type', 'name', 'kit', 'num_items', 'price', 'description', 'user', 'status'];

    protected $appends = ['reward_type_description'];

    public const REWARD_TYPE = ['P' => 'Producto', 'S' => 'Servicio'];

    public function getRewardTypeDescriptionAttribute()
    {
        return self::REWARD_TYPE[$this->reward_type] ?? '';
    }

    public static function getRewardTypes($excludeId = null)
    {
        if ($excludeId) {
            return array_filter(self::REWARD_TYPE, function ($label, $key) use ($excludeId) {
                return $key !== $excludeId;
            }, ARRAY_FILTER_USE_BOTH);
        }

        return self::REWARD_TYPE;
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getAllRewardsByCreator($creatorUser)
    {
        return self::where('user', $creatorUser)->get();
    }
}
