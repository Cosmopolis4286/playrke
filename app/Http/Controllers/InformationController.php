<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\UserVal;

class InformationController extends Controller
{
    use UserVal;

    public function terms()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        return view('modules.all.info-rke.terms', compact('wallet'));
    }

    public function about()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        return view('modules.all.info-rke.about', compact('wallet'));
    }

    public function privacy()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        return view('modules.all.info-rke.privacy', compact('wallet'));
    }

    public function helpCenter()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        $title = 'Centro de Ayuda';
        return view('modules.all.info-rke.help-center', compact('wallet', 'title'));
    }

    public function pds()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        return view('modules.all.info-rke.pds', compact('wallet'));
    }
}
