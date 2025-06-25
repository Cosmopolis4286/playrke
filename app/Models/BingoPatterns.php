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

        if (!$bingoPattern || !$bingoPattern->boxes) {
            return [];
        }

        $columns = [];

        if ($bingoPattern->type_pattern === 'UNIQUE') {
            $columns = explode(';', $bingoPattern->boxes);
        } else {
            $patternIds = explode(';', $bingoPattern->boxes);

            foreach ($patternIds as $patternId) {
                $boxes = self::getValueById($patternId, 'boxes');

                if ($boxes) {
                    $columns = array_merge($columns, explode(';', $boxes));
                }
            }
        }

        // Eliminar duplicados y vacíos
        return array_values(array_unique(array_filter($columns)));
    }
}
