<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\BPay;

class CryptoWalletController extends Controller
{

    public function index()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $wallet = Wallet::all()->where("usuario_id", $user);
        $BPay = BPay::where("user", $user)->first();
        $list_wallet = Wallet::where("usuario_id", $user)->get();

        return view('modules/user/crypto/index')->with([
            'wallets' => $wallet,
            'BPay' => $BPay,
            'list_wallet' => $list_wallet,
            'wallet' => $wallet,
        ]);
    }

    public function BPay()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $BPay = BPay::where("user", $user)->first();

        return view('modules/user/crypto/bpay')->with([
            'BPay' => $BPay,
            'wallet' => $wallet,
        ]);
    }

    public function create()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $list_wallet = Wallet::where("usuario_id", $user)->get();

        return view('modules/user/crypto/create')->with([
            'list_wallet' => $list_wallet,
            'wallet' => $wallet,
        ]);
    }

    public function store(Request $request)
    {
        $user = User::where('id', auth()->user()->id)->get();
        $wallet = new Wallet;
        $wallet->crypto_mon = $request->crypto_mon;
        $wallet->crypto_red = $request->crypto_red;
        $wallet->crypto_direcc =  $request->crypto_direcc;
        $wallet->usuario_id = $user->first()->id;
        $wallet->save();
        return redirect()->route('wallet.index');
    }

    public function storeBPay(Request $request)
    {
        $user = User::where('id', auth()->user()->id)->get();
        $bpay = new BPay;
        $bpay->amount_id = $request->amount_id;
        $bpay->usuario_id = $user->first()->id;
        $bpay->save();
        return redirect()->route('wallet.index');
    }
}
