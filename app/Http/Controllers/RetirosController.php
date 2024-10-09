<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Banks_VENS;
use App\Models\CommerceExchangeRate;
use App\Models\Retiro;
use App\Models\Wallet;
use App\Models\SaldoCongelado;
use App\Models\Transactions_BPay;
use App\Models\Transactions_VES;
use App\Http\Controllers\Controller;

class RetirosController extends Controller
{
    public function cryptos()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $esperas = Retiro::all()->where("estatus", 0);
        $retiros = Retiro::all()->where("estatus", 1);
        $bancos = Banks_VENS::all();
        $users = User::all();

        return view('modules/admin/retiros/cryptos')->with([
            'esperas' => $esperas,
            'retiros' => $retiros,
            'bancos' => $bancos,
            'user' => $user,
            'wallet' => $wallet,
            'users' => $users
        ]);
    }

    public function transk_bs()
    {
        $bancos = Banks_VENS::all();
        $users = User::all();
        $transactions_ves_apd = Transactions_VES::where('tipo', 2)
            ->whereIn('status', [1, 2])->orderBy('status')->orderBy('created_at', 'DESC')->get();
        $transactions_ves_crz = Transactions_VES::where('tipo', 2)
            ->whereIn('status', [3])->orderBy('status')->orderBy('created_at', 'DESC')->get();
        $wallet = Wallet::where("usuario_id", auth()->user()->id)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->get();

        return view('modules/admin/retiros/transk_bs')->with([
            'transactions_ves_apd' => $transactions_ves_apd,
            'transactions_ves_crz' => $transactions_ves_crz,
            'bancos' => $bancos,
            'CommerceExchangeRate' => $CommerceExchangeRate,
            'wallet' => $wallet,
            'users' => $users
        ]);
    }

    public function accept_bs($id)
    {
        $recarga = Transactions_VES::findOrFail($id);
        $recarga->status = 2;
        $wallet = Wallet::where("usuario_id", $recarga->user)->first();
        $wallet->balance = $wallet->balance + $recarga->monto;

        $sld_freeze = SaldoCongelado::where("user",  $recarga->user)
            ->where('coin', 2)->where('status', 0)->get();
        if (count($sld_freeze) > 0) {
            foreach ($sld_freeze as $saldoC) {
                $wallet->balance = $wallet->balance + $saldoC->monto;
                $saldoC->status = 1;
                $saldoC->save();
            }
        }
        $recarga->save();
        $wallet->save();
        return redirect()->back();
    }

    public function transk_BPay()
    {
        $users = User::all();
        $transactions_bpay_apd = Transactions_BPay::where('tipo', 2)
            ->whereIn('status', [1, 2])->orderBy('status')->orderBy('created_at', 'DESC')->get();
        $transactions_bpay_crz = Transactions_BPay::where('tipo', 2)
            ->whereIn('status', [3])->orderBy('status')->orderBy('created_at', 'DESC')->get();
        $wallet = Wallet::where("usuario_id", auth()->user()->id)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();

        return view('modules/admin/retiros/transk_bpay')->with([
            'transactions_bpay_apd' => $transactions_bpay_apd,
            'transactions_bpay_crz' => $transactions_bpay_crz,
            'wallet' => $wallet,
            'users' => $users
        ]);
    }

    public function accept_bpay($id)
    {
        $recarga = Transactions_BPay::findOrFail($id);
        $recarga->status = 2;
        $wallet = Wallet::where("usuario_id", $recarga->user)->first();
        $wallet->usdt = $wallet->usdt + $recarga->monto;

        $sld_freeze = SaldoCongelado::where("user",  $recarga->user)
            ->where('coin', 1)->where('status', 0)->get();
        if (count($sld_freeze) > 0) {
            foreach ($sld_freeze as $saldoC) {
                $wallet->usdt = $wallet->usdt + $saldoC->monto;
                $saldoC->status = 1;
                $saldoC->save();
            }
        }
        $recarga->save();
        $wallet->save();
        return redirect()->back();
    }
}
