<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Repositories\Users\Roles\RoleRepository;
// Traits
use App\Traits\Users\UserTrait;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\View;

class InformationController extends Controller
{
    use UserTrait;

    public function terms(): Response
    {
        return Inertia::render('shared/utils/info/Terms', [
            'title' => 'Términos y Condiciones',
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('shared/utils/info/About', [
            'title' => 'Quiénes Somos',
        ]);
    }

    public function privacy()
    {
        return Inertia::render('shared/utils/info/Privacy', [
            'title' => 'Política de Privacidad',
        ]);
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
        return redirect()->back();

        return view(
            'modules.shared.utils.info.power-of-knowledge',
            with(['title' => 'El Poder del Saber'])
        );
    }
}
