<?php

namespace App\Models\Games\Shared\Utils;

use App\Models\Games\Shared\Game;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class GameRule extends Model
{
    protected $table = "game_rules";
    protected $fillable = ['user', 'game', 'image', 'rules', 'encrypted', 'status'];

    public function Game()
    {
        return $this->belongsTo(Game::class, 'game');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public static function getByGame($game, $user = null, array $fields = ['*'])
    {
        $gameId = is_string($game) ? Game::getValueByName($game) : $game;

        return self::select($fields)
            ->where('game', $gameId)
            ->when($user, fn($query) => $query->where('user', $user))
            ->first();
    }

    public static function getValueByGame($game, $user = false, $value = 'rules')
    {
        $gameId = is_string($game) ? Game::getValueByName($game) : $game;

        return self::where('game', $gameId)
            ->when($user, fn($query) => $query->where('user', $user))
            ->value($value);
    }

    public static function existsCode($encrypted)
    {
        return self::where('encrypted', $encrypted)->exists();
    }
}
