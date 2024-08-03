<?php

namespace App\Http\Controllers;

use App\Models\Luck;
use App\Models\User;
use App\Models\Codes;
use App\Models\CommerceExchangeRate;
use App\Models\HistGame;
use App\Models\Wallet;
use App\Http\Controllers\Controller;
use App\Traits\UserVal;

class HistorialController extends Controller
{
    use UserVal;

    public function index()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        if ($user->user_type == 2) {
            $hist_games = HistGame::join('monedas', 'monedas.id', 'hist_games.coin')
                ->join('games', 'games.id', 'hist_games.game')->get();
            return view('modules/admin/historial/index')->with([
                'hist_games' => $hist_games,
                'wallet' => $wallet,
            ]);
        } elseif ($user->user_type == 1) {
            $hist_games = HistGame::where("user", $user)
                ->join('monedas', 'monedas.id', 'hist_games.coin')
                ->join('games', 'games.id', 'hist_games.game')->get();
            return view('modules/user/historial/index')->with([
                'hist_games' => $hist_games,
                'wallet' => $wallet,
            ]);
        }
    }

    public function codes()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();

        if (auth()->user()->user_type == 2) {
            $codes = Codes::all();
            return view('modules/admin/historial/codes')->with([
                'codes' => $codes,
                'wallet' => $wallet,
            ]);
        } elseif (auth()->user()->user_type == 1) {
            $codes = Codes::where("usuario_id", auth()->user()->id)->get();
            return view('modules/user/historial/codes')->with([
                'wallet' => $wallet,
                'codes' => $codes,
            ]);
        }
    }

    public function luck()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        if (auth()->user()->user_type == 2) {
            $users = User::all();
            $luck = Luck::all();
            return view('modules/admin/historial/luck')->with([
                'users' => $users,
                'luck' => $luck,
                'wallet' => $wallet,
            ]);
        } elseif (auth()->user()->user_type == 1) {
            $luck = Luck::all();
            return view('modules/user/historial/luck')->with([
                'user' => $user,
                'luck' => $luck,
                'wallet' => $wallet,
            ]);
        }
    }
}
