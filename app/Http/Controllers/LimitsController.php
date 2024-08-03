<?php

namespace App\Http\Controllers;

use App\Models\CommerceExchangeRate;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class LimitsController extends Controller
{
    public function index()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $limits = DB::table('limits')->get();
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->get();
        return view('modules/admin/limites/index')->with([
            'wallet' => $wallet,
            'CommerceExchangeRate' => $CommerceExchangeRate,
            'limits' => $limits
        ]);
    }

    public function store_dp(Request $request)
    {
        DB::table('limits')->where('id', 1)->update(['valor' => $request->min_dp_bs]);
        DB::table('limits')->where('id', 2)->update(['valor' => $request->max_dp_bs]);
        DB::table('limits')->where('id', 3)->update(['valor' => $request->min_dp_usdt]);
        return redirect()->route('limits.index');
    }

    public function store_rt(Request $request)
    {
        DB::table('limits')->where('id', 4)->update(['valor' => $request->com_rt]);
        DB::table('limits')->where('id', 5)->update(['valor' => $request->min_rt]);
        DB::table('limits')->where('id', 6)->update(['valor' => $request->max_rt_bs]);
        DB::table('limits')->where('id', 7)->update(['valor' => $request->max_rt_usdt]);
        return redirect()->route('limits.index');
    }

    public function store_transfer(Request $request)
    {
        DB::table('limits')->where('id', 8)->update(['valor' => $request->com_transfer]);
        DB::table('limits')->where('id', 9)->update(['valor' => $request->min_transfer]);
        DB::table('limits')->where('id', 10)->update(['valor' => $request->no_transfer]);
        return redirect()->route('limits.index');
    }
}
