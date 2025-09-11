<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BingoRoundDraft extends Model
{
    use HasFactory;

    protected $table = 'bingo_rounds_drafts';

    protected $fillable = [
        'bingo_room_id',
        'automatic_bingo_room_id',
        'rounds',
        'prize_types',
        'bingo_patterns',
        'prize_values',
        'status',
    ];

    protected $casts = [
        'prize_types'    => 'array',
        'bingo_patterns' => 'array',
        'prize_values'   => 'array',
    ];

    public static function getById(int $id, array $fields = ['*']): ?self
    {
        return self::select($fields)->find($id);
    }

    public static function getValueById(int $id, string $column)
    {
        return self::where('id', $id)->value($column);
    }

    public static function getByRoom(int $roomId, array $fields = ['*']): ?self
    {
        return self::select($fields)->where('bingo_room_id', $roomId)->first();
    }

    public static function getByAutomaticRoom(int $roomId, array $fields = ['*']): ?self
    {
        return self::select($fields)->where('automatic_bingo_room_id', $roomId)->first();
    }
}
