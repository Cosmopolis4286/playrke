<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
// Traits
use App\Traits\Users\UserTrait;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para páginas de información compartida
 */
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

    public function privacy(): Response
    {
        return Inertia::render('shared/utils/info/Privacy', [
            'title' => 'Política de Privacidad',
        ]);
    }

    public function helpCenter(): Response
    {
        return Inertia::render('shared/utils/info/HelpCenter', [
            'title' => 'Centro de Ayuda',
        ]);
    }

    public function powerOfKnowledge(): Response
    {
        return Inertia::render('shared/utils/info/games/PowerOfKnowledge', [
            'title' => 'El Poder del Saber',
        ]);
    }
}
