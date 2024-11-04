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
use App\Models\UserType;
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

        $data = [
            'title' => $title,
            'wallet' => $wallet,
            'exchangeRate' => $exchangeRate,
        ];

        $creatorType = UserType::getValueById($user->user_type, 'prefix');

        // Datos específicos según el tipo de usuario
        switch ($creatorType) {
            case 'user': // Jugador
                $view = 'modules/user/widgets/index';
                break;

            case 'admin': // Super Admin
                $data = array_merge($data, [
                    'mmoney' => $this->mmoney(),
                    // 'mmoneyUSDT' => $this->mmoneyUSDT(),
                    // 'balance_tBs' => $this->balance_tBs(),
                    // 'balance_tUSDT' => $this->balance_tUSDT(),
                    // 'counuser_type' => $this->counuser_type(),
                    // 'depositsBPay' => $this->depositsBPay(),
                    // 'retirosBPay' => $this->retirosBPay(),
                ]);
                $view = 'modules/admin/widgets/index';
                break;

            case 'commerce': // Bancas
                $data = array_merge($data, [
                    'mmoney' => $this->mmoney(),
                    'mmoneyUSDT' => $this->mmoneyUSDT(),
                    'balance_tBs' => $this->balance_tBs(),
                    'balance_tUSDT' => $this->balance_tUSDT(),
                ]);
                $view = 'modules/commerce/widgets/index';
                break;

            case 'seller': // Taquilleros/Vendedor
                $data = array_merge($data, [
                    'mmoney' => $this->mmoney(),
                    'mmoneyUSDT' => $this->mmoneyUSDT(),
                    'balance_tBs' => $this->balance_tBs(),
                    'balance_tUSDT' => $this->balance_tUSDT(),
                    'counuser_type' => $this->counuser_type(),
                ]);
                $view = 'modules/seller/widgets/index';
                break;

            case 5: // Prooveedor
                $data = array_merge($data, [
                    'mmoney' => $this->mmoney(),
                    'mmoneyUSDT' => $this->mmoneyUSDT(),
                    'balance_tBs' => $this->balance_tBs(),
                    'balance_tUSDT' => $this->balance_tUSDT(),
                    'counuser_type' => $this->counuser_type(),
                ]);
                $view = 'modules/provider/widgets/index';
                break;

            default:
                // Manejar otros casos si es necesario
                abort(404);
        }

        return view($view)->with($data);
    }

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
        $data = Wallet::sum('usdt');
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
