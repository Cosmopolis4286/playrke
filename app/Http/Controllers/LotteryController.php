<?php

namespace App\Http\Controllers;

use App\Traits\UserVal;
use App\Traits\EncryptRKE;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Coin;
use App\Models\HistGame;
use App\Http\Controllers\Controller;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\GameModes;
use App\Models\JackpotType;
use App\Traits\Filters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LotteryController extends Controller
{
    use EncryptRKE;
    use UserVal;
    use Filters;

    public function lotteryResults($game)
    {

        return $this->handleViewData(['route' => 'modules.game.polla.form.index', 'data' => $viewData]);
    }
}
