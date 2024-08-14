<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomPrizes extends Model
{
  protected $table = "bingo_rooms_prizes";
  protected $fillable = [
    'game_mode', 'room', 'bg_prize', 'serials_prize', 'diagonal',
    'four_corners', 'bg_cross', 'sm_cross', 'line', 'created_at', 'updated_at'
  ];

  public function GameMode()
  {
      return $this->belongsTo(GameModes::class, 'game_mode', 'game_mode');
  }

  public function Room()
  {
    return $this->belongsTo(BingoRoom::class, 'room');
  }
}
