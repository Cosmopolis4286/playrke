<?php

namespace App\Http\Controllers;

use App\Traits\UserVal;
use App\Traits\EncryptRKE;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Coin;
use App\Models\HistGame;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PollaExpressController extends Controller
{
    use EncryptRKE;
    use UserVal;
}
