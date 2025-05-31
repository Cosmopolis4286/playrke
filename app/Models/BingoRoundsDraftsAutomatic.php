<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BingoRoundsDraftsAutomatic extends Model
{
    use HasFactory;

    protected $table = 'automatic_bingo_rounds_drafts';

    protected $fillable = [
        'automatic_bingo_id',
        'rounds',
        'prize_types',
        'play_types',
        'prize_values',
        'status',
    ];

    protected $casts = [
        'prize_types'  => 'array',
        'play_types'   => 'array',
        'prize_values' => 'array',
    ];

    /**
     * Get a draft by its ID.
     */
    public static function getById(int $id, array $fields = ['*']): ?self
    {
        return self::select($fields)->find($id);
    }

    /**
     * Get a single column value by draft ID.
     */
    public static function getValueById(int $id, string $column)
    {
        return self::where('id', $id)->value($column);
    }

    /**
     * Get the draft by room ID.
     */
    public static function getByRoom(int $roomId, array $fields = ['*']): ?self
    {
        return self::select($fields)->where('automatic_bingo_id', $roomId)->first();
    }
}
