<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use App\Traits\UserVal;
use Illuminate\Support\Facades\Auth;

class InformationController extends Controller
{
    use UserVal;

    public function terms()
    {
        return view('modules.all.info-rke.terms', with(['wallet' => $this->Wallet(Auth::id())]));
    }

    public function about()
    {
        return view('modules.all.info-rke.about', with(['wallet' => $this->Wallet(Auth::id())]));
    }

    public function privacy()
    {
        return view('modules.all.info-rke.privacy', with(['wallet' => $this->Wallet(Auth::id())]));
    }

    public function helpCenter()
    {
        return view(
            'modules.all.info-rke.help-center',
            with(['wallet' => $this->Wallet(Auth::id()), 'title' => 'Centro de Ayuda'])
        );
    }

    public function powerOfKnowledge()
    {
        $userType = User::getValueById(Auth::id(), 'user_type');

        if ($userType != 1) return redirect()->back();

        return view('modules.all.info-rke.power-of-knowledge', with(['wallet' => $this->Wallet(Auth::id())]));
    }

    public function getTimeServer($timeZone = 'America/Caracas')
    {
        date_default_timezone_set($timeZone);
        return time();
    }
}
