<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomPrizes extends Model
{
  protected $table = "bingo_rooms_prizes";
  protected $fillable = [
    'room', 'bg_prize', 'serials_prize', 'diagonal',
    'four_corners', 'bg_cross', 'sm_cross', 'line', 'created_at', 'updated_at'
  ];

  public function Room()
  {
    return $this->belongsTo(BingoRoom::class, 'room');
  }
}
