<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Country;

class ReferralController extends Controller
{
    public function index(Request $request, $referralCode)
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $referidos = User::where("referred_by", $user)->get();
        $pais = Country::all();

        return view('modules/user/referidos/index')->with([
            'referralLink' => $request->root() . '/' . $referralCode,
            'referidos' => $referidos,
            'pais' => $pais,
            'wallet' => $wallet,
        ]);
    }

    public function link(Request $request, $referralCode)
    {
        if (!$request->hasCookie('referral')) {
            $cookie = cookie('referral', $referralCode, 15);
            return redirect('/')->withCookie($cookie);
        }
        return redirect('/');
    }
}
