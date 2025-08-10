<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

// Models
use App\Models\User;
// Traits
use App\Traits\Users\UserValTrait;
use Illuminate\Support\Facades\Auth;

class InformationController extends Controller
{
    use UserValTrait;

    public function terms()
    {
        return view('modules.all.info-rke.terms');
    }

    public function about()
    {
        return view('modules.all.info-rke.about');
    }

    public function privacy()
    {
        return view('modules.all.info-rke.privacy');
    }

    public function helpCenter()
    {
        return view(
            'modules.all.info-rke.help-center',
            with(['title' => 'Centro de Ayuda'])
        );
    }

    public function powerOfKnowledge()
    {
        $creatorType = User::getValueById(Auth::id(), 'user_type');

        if ($creatorType != 1) return redirect()->back();

        return view('modules.all.info-rke.power-of-knowledge');
    }

    public function getTimeServer($timeZone = 'America/Caracas')
    {
        date_default_timezone_set($timeZone);
        return time();
    }
}
