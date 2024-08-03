<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Traits\UserVal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\TriviaMulti;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    use UserVal;

    public function index()
    {
        $user = $this->user();
        $exchangeRate = ExchangeRate::first();
        $wallet = $this->wallet($user->id);
        $title = 'Tablero';

        // // Obtener el código del país asociado al usuario
        // $country = User::where('id', Auth::id())->value('country');
        // session('countryCode', Country::getCountryById($country));

        // dd(session('countryCode'));

        // Datos comunes
        $data = [
            'title' => $title,
            'wallet' => $wallet,
            'exchangeRate' => $exchangeRate,
        ];

        // Datos específicos según el tipo de usuario
        switch ($user->user_type) {
            case 1: // Jugador
                $view = 'modules/user/gadgets/index';
                break;

            case 2: // Super Admin
                $data = array_merge($data, [
                    'mmoney' => $this->mmoney(),
                    // 'mmoneyUSDT' => $this->mmoneyUSDT(),
                    // 'balance_tBs' => $this->balance_tBs(),
                    // 'balance_tUSDT' => $this->balance_tUSDT(),
                    // 'counuser_type' => $this->counuser_type(),
                    // 'depositsBPay' => $this->depositsBPay(),
                    // 'retirosBPay' => $this->retirosBPay(),
                ]);
                $view = 'modules/admin/gadgets/index';
                break;

            case 3: // Bancas
                $data = array_merge($data, [
                    'mmoney' => $this->mmoney(),
                    'mmoneyUSDT' => $this->mmoneyUSDT(),
                    'balance_tBs' => $this->balance_tBs(),
                    'balance_tUSDT' => $this->balance_tUSDT(),
                ]);
                $view = 'modules/commerce/gadgets/index';
                break;

            case 4: // Taquilleros/Vendedor
                $data = array_merge($data, [
                    'mmoney' => $this->mmoney(),
                    'mmoneyUSDT' => $this->mmoneyUSDT(),
                    'balance_tBs' => $this->balance_tBs(),
                    'balance_tUSDT' => $this->balance_tUSDT(),
                    'counuser_type' => $this->counuser_type(),
                ]);
                $view = 'modules/seller/gadgets/index';
                break;

            default:
                // Manejar otros casos si es necesario
                abort(404);
        }

        return view($view)->with($data);
    }


    // public function upd_ts(Request $request)
    // {
    //     DB::table("ExchangeRates")->update(['ExchangeRate_RKE' => $request->ts_bcv, 'fecha' => $request->fecha]);
    // }

    public  function mmoney()
    {
        return Wallet::where('user', 1)->sum('balance');
    }
    public  function mmoneyUSDT()
    {
        return Wallet::where('user', 1)->sum('usdt');
    }

    public  function count_GRsD()
    {
        $data = DB::table("triv_multi")->where('status', 1)->count();
        return $data;
    }
    public  function count_GRsBr()
    {
        $data = DB::table("triv_multi")->where('status', 0)->count();
        return $data;
    }
    public  function balance_tBs()
    {
        return Wallet::where('coin', 2)->sum('balance');
    }
    public  function balance_tUSDT()
    {
        $data = DB::table("Wallets")->sum('usdt');
        return $data;
    }
    public  function balance_inGBs($id)
    {
        return TriviaMulti::where('status', 1)->where('coin', $id)->sum('bote');
    }
    public  function balance_inGUSDT()
    {
        $data = DB::table("triv_multi")->where('status', 1)->where('coin', 1)->sum('bote');
        return $data;
    }
    public  function depositsBs()
    {
        $data = DB::table("transactions_ves")->where('tipo', 1)->where('status', 0)->count();
        return $data;
    }
    public  function retirosBs()
    {
        $data = DB::table("transactions_ves")->where('tipo', 2)->where('status', 0)->count();
        return $data;
    }
    /*public  function depositsBlock()
    {
        $data = DB::table("dp_cryptos")->where('estatus', 0)->count();
        return $data;
    }
    public  function retirosBlock()
    {
        $data = DB::table("rt_cryptos")->where('estatus', 0)->count();
        return $data;
    }*/
    public  function depositsBPay()
    {
        $data = DB::table("transactions_bpay")->where('tipo', 1)->where('status', 1)->count();
        return $data;
    }
    public  function retirosBPay()
    {
        $data = DB::table("transactions_bpay")->where('tipo', 2)->where('status', 1)->count();
        return $data;
    }
    public  function counuser_type()
    {
        return User::where('user_type', 1)->count();
    }
}
