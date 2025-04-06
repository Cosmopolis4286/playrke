<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaCreator extends Model
{
    protected $table = 'polla_creator';
    protected $fillable = [
        'draw',
        'creator_type',
        'creator_id',
        'creator_commn',
        'ticket_price',
        'status',
        'created_at',
        'updated_at'
    ];

    public function Draw()
    {
        return $this->belongsTo(PollaDraw::class, 'draw');
    }

    public function CreatorType()
    {
        return $this->belongsTo(UserType::class, 'creator_type');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByDraw($draw, $fields = ['*'])
    {
        return self::select($fields)->where('draw', $draw)->first();
    }

    public static function getValueByDraw($draw, $value)
    {
        return self::where('draw', $draw)->value($value);
    }

    public static function getByCreatorDraw($creator, $draw, $fields = ['*'])
    {
        return self::select($fields)->where($creator['type'], $creator['id'])->where('draw', $draw)->first();
    }

    public static function getValueByCreatorDraw($creator, $draw, $value = 'id')
    {
        return self::where($creator['type'], $creator['id'])->where('draw', $draw)->value($value);
    }
}
