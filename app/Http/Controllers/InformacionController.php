<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Traits\UserVal;

class InformacionController extends Controller
{
    use UserVal;

    public function terminos()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        return view('modules.user.info.terminos', compact('wallet'));
    }

    public function acerca()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        return view('modules.user.info.acerca', compact('wallet'));
    }

    public function privacidad()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        return view('modules.user.info.privacidad', compact('wallet'));
    }

    public function ayuda()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        $title = 'Centro de Ayuda';
        return view('modules.user.info.ayuda', compact('wallet', 'title'));
    }

    public function pds()
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        return view('modules.user.info.pds', compact('wallet'));
    }
}
