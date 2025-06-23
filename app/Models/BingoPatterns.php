<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoPatterns extends Model
{
    protected $table = 'bingo_patterns';
    protected $fillable = ['type_pattern', 'name', 'user', 'boxes', 'status'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getAllByUser($user, $fields = ['*'], $status = null)
    {
        return self::select($fields)->where('user', $user)
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })->get();
    }

    public static function existsBingoPlay($column, $value, $user)
    {
        return self::where($column, $value)->where('user', $user)->exists();
    }

    public static function getColumnsBoxesById($id)
    {
        $bingoPattern = self::select('type_pattern', 'boxes')->where('id', $id)->first();

        if ($bingoPattern->type_pattern === 'UNIQUE') {
            $columns = explode(';', $bingoPattern->boxes);
        } else {
            $patterns = explode(';', $bingoPattern->boxes);
            $columns = [];

            foreach ($patterns as $patternId) {
                $columns = $columns + [self::getValueById($patternId, 'boxes')];
            }
        }
        return $columns;
    }
}
