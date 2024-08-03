<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Banco_CHL;
use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Models\CuentaBancaria;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\validationCuentabancaria;

class BancosController extends Controller
{
    public function index()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        if ($wallet->coin != 4) {
            return redirect()->back();
        }
        $accounts = CuentaBancaria::all()->where("user", $user);

        return view('modules/user/banco/index')->with([
            'accounts' => $accounts,
            'wallet' => $wallet,
        ]);
    }

    public function create()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        if ($wallet->coin != 4) {
            return redirect()->back();
        }
        $banks = DB::table('banks_chl')->get();
        $accounts = CuentaBancaria::all()->where("user", $user);
        if (count($accounts) == 2) {
            return redirect()->route('banco.index');
        }
        $account_ft = CuentaBancaria::all()->where("user", $user)->first();
        if ($account_ft == null) {
            $account_ft = null;
        } else {
            $account_ft = $account_ft->rut;
        }

        return view('modules/user/banco/create')->with([
            'account' => $account_ft,
            'banks' => $banks,
            'wallet' => $wallet,
        ]);
    }
    public function store(Request $request)
    {
        $cuentaBancaria = new CuentaBancaria;
        $cuentaBancaria->rut = $request->rut;
        $cuentaBancaria->bank = $request->bank;
        $cuentaBancaria->type = $request->type_account;
        $cuentaBancaria->num_account = $request->num_account;
        $cuentaBancaria->user = auth()->user()->id;
        $cuentaBancaria->save();
        return redirect()->route('banco.index');
    }

    public function edit($id)
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        if ($wallet->coin != 4) {
            return redirect()->back();
        }
        $banks = DB::table('banks_chl')->get();
        $cuentaBancaria = CuentaBancaria::findOrFail($id);

        return view('modules/user/banco/edit')->with([
            'bancos' => $banks,
            'cuentaBancaria' => $cuentaBancaria,
            'user' => $user,
            'wallet' => $wallet,
        ]);
    }

    public function update(Request $request, $id)
    {
        $cuentaBancaria = CuentaBancaria::findOrFail($id);
        $banco_id = DB::table('banks_chl')->where("codigo", $request->codigo_banco)->get();
        $cuentaBancaria->codigo_banco = $request->codigo_banco;
        $cuentaBancaria->nro_cuenta = $request->nro_cuenta;
        $cuentaBancaria->banco_id = $banco_id[0]->id;

        $cuentaBancaria->update();
        return redirect()->route('banco.index');
    }
}
