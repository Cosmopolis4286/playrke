<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rewards extends Model
{
    protected $table = 'rewards';
    protected $fillable = ['code', 'kit', 'num_items', 'price', 'description', 'user', 'status'];

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
