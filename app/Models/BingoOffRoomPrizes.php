<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoOffRoomPrizes extends Model
{
  protected $table = "bingo_off_rooms_prizes";
  protected $primaryKey = 'id';
  protected $fillable = [
    'id', 'created_at', 'room', 'bg_prize', 'serials_prize', 'diagonal',
    'four_corners', 'bg_cross', 'sm_cross', 'line', 'updated_at'
  ];

  public function Room()
  {
    return $this->belongsTo(BingoOffRoom::class, 'room');
  }
}
