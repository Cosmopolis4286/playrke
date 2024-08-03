<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transactions_VES;
use App\Models\Banks_VENS;
use App\Models\DP_Block;
use App\Models\Wallet;
use App\Models\CommerceExchangeRate;
use App\Models\SaldoCongelado;
use App\Models\Transactions_BPay;
use App\Models\User;
use Illuminate\Http\Request;

class RecargasController extends Controller
{
    public function transk_bs()
    {
        $bancos = Banks_VENS::all();
        $users = User::all();
        $transactions_ves_apd = Transactions_VES::where('tipo', 1)
            ->orderBy('status')->orderBy('created_at', 'DESC')->whereIn('status', [1, 2])->get();
        $transactions_ves_crz = Transactions_VES::whereIn('status', [3, 4])
            ->orderBy('status')->orderBy('created_at', 'DESC')
            ->leftjoin('transk_motives', 'transk_motives.id_mtv', 'transactions_ves.motivo')->get();
        $wallet = Wallet::where("usuario_id", auth()->user()->id)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();

        return view('modules/admin/recargas/transk_bs')->with([
            'transactions_ves_apd' => $transactions_ves_apd,
            'transactions_ves_crz' => $transactions_ves_crz,
            'bancos' => $bancos,
            'wallet' => $wallet,
            'users' => $users
        ]);
    }

    public function accept_bs($id)
    {
        $recarga = Transactions_VES::findOrFail($id);
        if ($recarga->status == 1) {
            $recarga->status = 2;
            $wallet = Wallet::where("usuario_id", $recarga->user)->first();
            $wallet->saldo = $wallet->saldo + $recarga->monto;

            $sld_freeze = SaldoCongelado::where("user",  $recarga->user)
                ->where('coin', 2)->where('status', 0)->get();
            if (count($sld_freeze) > 0) {
                foreach ($sld_freeze as $saldoC) {
                    $wallet->saldo = $wallet->saldo + $saldoC->monto;
                    $saldoC->status = 1;
                    $saldoC->save();
                }
            }
            $recarga->save();
            $wallet->save();
            return redirect()->back();
        } else {
            return redirect()->back();
        }
    }

    public function decline_bs(Request $request)
    {
        $recarga = Transactions_VES::findOrFail($request->id_transk);
        $recarga->status = 4;
        $recarga->motivo = $request->motivo_dpr;
        $recarga->save();
        return redirect()->back();
    }

    public function freeze_bs($id)
    {
        $recarga = Transactions_VES::findOrFail($id);
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->get();

        return view('modules/admin/recargas/freeze')->with([
            'recarga' => $recarga,
            'wallet' => $wallet,
            'CommerceExchangeRate' => $CommerceExchangeRate
        ]);
    }

    public function run_freeze(Request $request)
    {
        $recarga = Transactions_VES::findOrFail($request->id_transk);
        $sd_freeze = new SaldoCongelado;
        $sd_freeze->coin = 2;
        $sd_freeze->monto = $request->monto;
        $sd_freeze->recarga_id = $recarga->id;
        $sd_freeze->user =  $recarga->user;
        $sd_freeze->status = 0;
        $sd_freeze->save();

        $recarga->status = 3;
        $recarga->motivo = $request->motivo;
        $recarga->update();
        return redirect('transk_bs');
    }

    public function liberate_bs($id)
    {
        $recarga = Transactions_VES::findOrFail($id);
        $sd_freeze = new SaldoCongelado;
        $sd_freeze->status = 1;
        $recarga->status = 2;
        $recarga->motivo = 5;
        $sd_freeze->save();
        $recarga->save();
        return redirect()->back();
    }

    public function transk_BPay()
    {
        $users = User::all();
        $transactions_bpay_apd = Transactions_BPay::where('tipo', 1)
            ->whereIn('status', [1, 2])->orderBy('status')->orderBy('created_at', 'DESC')->get();
        $transactions_bpay_crz = Transactions_BPay::whereIn('status', [3, 4])
            ->leftjoin('transk_motives', 'transk_motives.id_mtv', 'transactions_bpay.motivo')
            ->orderBy('status')->orderBy('created_at', 'DESC')->get();
        $wallet = Wallet::where("usuario_id", auth()->user()->id)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();

        return view('modules/admin/recargas/transk_bpay')->with([
            'transactions_bpay_apd' => $transactions_bpay_apd,
            'transactions_bpay_crz' => $transactions_bpay_crz,
            'wallet' => $wallet,
            'users' => $users
        ]);
    }

    public function accept_bpay($id)
    {
        $recarga = Transactions_BPay::findOrFail($id);
        if ($recarga->status == 1) {
            $recarga->status = 2;
            $wallet = Wallet::where("usuario_id", $recarga->user)->first();
            $wallet->saldo = $wallet->saldo + $recarga->monto;

            $sld_freeze = SaldoCongelado::where("user",  $recarga->user)
                ->where('coin', 1)->where('status', 0)->get();
            if (count($sld_freeze) > 0) {
                foreach ($sld_freeze as $saldoC) {
                    $wallet->saldo = $wallet->saldo + $saldoC->monto;
                    $saldoC->status = 1;
                    $saldoC->save();
                }
            }
            $recarga->save();
            $wallet->save();
            return redirect()->back();
        } else {
            return redirect()->back();
        }
    }

    public function decline_bpay(Request $request)
    {
        $recarga = Transactions_BPay::findOrFail($request->id_transk);
        $recarga->status = 4;
        $recarga->motivo = $request->motivo_dpr;
        $recarga->save();
        return redirect()->back();
    }

    public function freeze_bpay($id)
    {
        $recarga = Transactions_BPay::findOrFail($id);

        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->get();

        return view('modules/admin/recargas/freeze_bpay')->with([
            'recarga' => $recarga,
            'wallet' => $wallet,
            'CommerceExchangeRate' => $CommerceExchangeRate
        ]);
    }

    public function run_freeze_bpay(Request $request)
    {
        $recarga = Transactions_BPay::findOrFail($request->id_transk);
        $sd_freeze = new SaldoCongelado;
        $sd_freeze->coin = 1;
        $sd_freeze->monto = $request->monto;
        $sd_freeze->recarga_id = $recarga->id;
        $sd_freeze->user =  $recarga->user;
        $sd_freeze->status = 0;
        $sd_freeze->save();

        $recarga->status = 3;
        $recarga->motivo = $request->motivo;
        $recarga->update();
        return redirect('transk_BPay');
    }

    public function liberate_bpay($id)
    {
        $recarga = Transactions_BPay::findOrFail($id);
        $sd_freeze = new SaldoCongelado;
        $sd_freeze->status = 1;
        $recarga->status = 2;
        $recarga->motivo = 5;
        $sd_freeze->save();
        $recarga->save();
        return redirect()->back();
    }

    public function cryptos()
    {
        $users = User::all();
        $dpsCryptos = DP_Block::all()->where("estatus", 0);
        $dpsACryptos = DP_Block::all()->where("estatus", 1);
        $dpsRCryptos = DP_Block::all()->where("estatus", 3);
        $wallet = Wallet::where("usuario_id", auth()->user()->id)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();

        return view('modules/admin/recargas/crypto')->with([
            'dpsCryptos' => $dpsCryptos,
            'dpsACryptos' => $dpsACryptos,
            'dpsRCryptos' => $dpsRCryptos,
            'wallet' => $wallet,
            'users' => $users
        ]);
    }
}
