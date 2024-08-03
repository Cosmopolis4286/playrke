<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\question;
use App\Models\TriviaIndv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;


class TrikiController extends Controller
{
    public function rooms()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        if (auth()->user()->user_type == 2) {
            return view('modules/admin/trivia/rooms')->with([
                'wallet' => $wallet,
            ]);
        } elseif ((auth()->user()->user_type == 1)) {
            return view('modules/user/triki/rooms')->with([
                'wallet' => $wallet

            ]);
        }
    }

    public function pdf()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $pdf = Pdf::loadView('modules.user.triki.pdf', compact('wallet'));
        return $pdf->stream();

        //return $pdf->download('hh.pdf');
    }
}
