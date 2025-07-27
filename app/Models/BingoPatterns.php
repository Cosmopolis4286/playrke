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
        $bingoPattern = self::select('type_pattern', 'boxes')->find($id);

        if (empty($bingoPattern?->boxes)) {
            return [];
        }

        $typePattern = $bingoPattern->type_pattern;

        if ($typePattern === 'UNIQUE') {
            $columns = explode(';', $bingoPattern->boxes);
            // Si se desea mantener la estructura con una única clave para UNIQUE
            return [
                'type' => $typePattern,
                'columns' => array_values(array_unique(array_filter($columns)))
            ];
        }

        $columns = [];

        foreach (explode(';', $bingoPattern->boxes) as $patternId) {
            $boxes = self::getValueById($patternId, 'boxes');

            if ($boxes) {
                $boxArray = array_values(array_unique(array_filter(explode(';', $boxes))));
                $columns[$patternId] = $boxArray;
            }
        }

        return ['type' => $typePattern, 'columns' => $columns];
    }
}
