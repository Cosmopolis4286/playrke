<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;

// Traits
use App\Traits\Users\UserTrait;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\View;

class InformationController extends Controller
{
    use UserTrait;

    public function terms()
    {
        return view('modules.shared.utils.info.terms');
    }

    public function about(): Response
    {
        return Inertia::render('shared/utils/info/About', [
            'html' => View::make('modules.shared.utils.info.about')->render(),
        ]);
    }

    public function privacy()
    {
        return view('modules.shared.utils.info.privacy');
    }

    public function helpCenter()
    {
        return view(
            'modules.shared.utils.info.help-center',
            with(['title' => 'Centro de Ayuda'])
        );
    }

    public function powerOfKnowledge()
    {
        $creatorType = User::getValueById(Auth::id(), 'user_type');

        if ($creatorType != 1) return redirect()->back();

        return view('modules.shared.utils.info.power-of-knowledge');
    }

    public function getTimeServer($timeZone = 'America/Caracas')
    {
        date_default_timezone_set($timeZone);
        return time();
    }
}
