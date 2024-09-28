<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\UserVal;
use Illuminate\Support\Facades\Auth;

class InformationController extends Controller
{
    use UserVal;

    public function terms()
    {
        $wallet = $this->Wallet(Auth::id());
        return view('modules.all.info-rke.terms', compact('wallet'));
    }

    public function about()
    {
        $wallet = $this->Wallet(Auth::id());
        return view('modules.all.info-rke.about', compact('wallet'));
    }

    public function privacy()
    {
        $wallet = $this->Wallet(Auth::id());
        return view('modules.all.info-rke.privacy', compact('wallet'));
    }

    public function helpCenter()
    {
        return view(
            'modules.all.info-rke.help-center',
            with(['wallet' => $this->Wallet(Auth::id()), 'title' => 'Centro de Ayuda'])
        );
    }

    public function pds()
    {
        $wallet = $this->Wallet(Auth::id());
        return view('modules.all.info-rke.pds', compact('wallet'));
    }

    public function getTimeServer($timeZone = 'America/Caracas')
    {
        date_default_timezone_set($timeZone);

        return time();
    }
}
