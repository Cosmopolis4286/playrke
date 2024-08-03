<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Wallet;

class DoubleController extends Controller
{
    public function index()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        return view('modules/user/double/index')->with([
            'wallet' => $wallet,
        ]);
    }
}
