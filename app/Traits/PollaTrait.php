<?php

namespace App\Traits;

use App\Models\PollaCreator;
use App\Models\PollaDraw;
use App\Models\PollaDrawConfig;
use App\Models\CoinGameRoom;
use App\Models\CommerceExchangeRate;
use App\Models\CustomerExchangeRate;
use App\Models\GameExchangeRate;
use App\Models\Games;
use App\Models\PollaTypes;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait PollaTrait
{
    public function getFormattedTime($gameType, $hour = 'hour_b')
    {
        $hourClose = PollaTypes::getValueById($gameType, $hour);
        return date('H:i', strtotime($hourClose));
    }
}
