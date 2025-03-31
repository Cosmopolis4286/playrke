<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaWinners extends Model
{
  protected $table = "polla_winners";
  protected $fillable = ['draw', 'player_ticket', 'status'];

  public function Draw()
  {
    return $this->belongsTo(PollaDraw::class, 'draw');
  }

  public function PlayerTicket()
  {
    return $this->belongsTo(PollaTickets::class, 'player_ticket');
  }

  static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  static function getByDraw($draw, $fields = ['*'])
  {
    return self::select($fields)->where('draw', $draw)->first();
  }

  static function getValueByDraw($draw, $value)
  {
    return self::where('draw', $draw)->value($value);
  }
}
