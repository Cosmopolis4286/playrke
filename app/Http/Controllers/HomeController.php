<?php

namespace App\Http\Controllers;

use App\Traits\UserVal;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TriviaMulti;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;

class HomeController extends Controller
{
    use UserVal;

    public function index()
    {
        $user = $this->user(['id', 'user_type']);
        $userID = $user->id;

        $data = [
            'title' => 'Tablero',
            'wallet' => $this->wallet($userID),
            'exchangeRate' => $exchangeRate ?? 1,
            'balance' => $this->getBalance($userID),
        ];

        $userType = UserType::getValueById($user->user_type, 'prefix');

        // Configuración de datos adicionales para cada tipo de usuario
        $extraData = [
            'user' => [],
            'admin' => [],
            'commerce' => [],
            'seller' => [],
            'seller' => []
        ];

        // Fusionar datos adicionales si existen para el tipo de usuario actual
        $data = array_merge($data, $extraData[$userType] ?? []);

        // Construir la ruta de la vista dinámicamente
        $view = "modules/{$userType}/widgets/index";

        return view($view)->with($data);
    }

    public function getBalance($userID)
    {
        return Wallet::where('user', $userID)->sum('balance');
    }

    public function balanceUSDT($userID)
    {
        return Wallet::where('user', $userID)->sum('usdt');
    }

    public function count_GRsD()
    {
        return DB::table("triv_multi")->where('status', 1)->count();
    }

    public function count_GRsBr()
    {
        return DB::table("triv_multi")->where('status', 0)->count();
    }

    public function balance_tBs()
    {
        return Wallet::where('coin', 2)->sum('balance');
    }

    public  function balance_tUSDT()
    {
        return Wallet::sum('usdt');
    }

    public  function balance_inGBs($id)
    {
        return TriviaMulti::where('status', 1)->where('coin', $id)->sum('bote');
    }

    public  function balance_inGUSDT()
    {
        return DB::table("triv_multi")->where('status', 1)->where('coin', 1)->sum('bote');
    }

    public  function depositsBs()
    {
        return DB::table("transactions_ves")->where('tipo', 1)->where('status', 0)->count();
    }

    public  function retirosBs()
    {
        return DB::table("transactions_ves")->where('tipo', 2)->where('status', 0)->count();
    }

    public function depositsBPay()
    {
        return DB::table("transactions_bpay")->where('tipo', 1)->where('status', 1)->count();
    }

    public function retirosBPay()
    {
        return DB::table("transactions_bpay")->where('tipo', 2)->where('status', 1)->count();
    }

    public function counuser_type()
    {
        return User::where('user_type', 1)->count();
    }
}
