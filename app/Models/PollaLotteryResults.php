<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaLotteryResults extends Model
{
  protected $table = "polla_lottery_results";
  protected $fillable = ['lottery_id', 'date', 'time', 'number'];

  public function Lottery()
  {
    return $this->belongsTo(Lotteries::class, 'lottery_id');
  }

  public static function getLotteryResult($date, $time = '')
  {
    $query = self::with('Lottery')->where('date', $date);

    if ($time) {
      $query->where('time', $time);
    }

    return $query->get();
  }
}
