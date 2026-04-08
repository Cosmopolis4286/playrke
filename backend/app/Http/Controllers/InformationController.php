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
        return Inertia::render('Utils/Info/Terms', [
            'title' => 'Términos y Condiciones',
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('Utils/Info/About', [
            'title' => 'Quiénes Somos',
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Utils/Info/Privacy', [
            'title' => 'Política de Privacidad',
        ]);
    }

    public function helpCenter(): Response
    {
        return Inertia::render('Utils/Info/HelpCenter', [
            'title' => 'Centro de Ayuda',
        ]);
    }

    public function powerOfKnowledge(): Response
    {
        return Inertia::render('Utils/Info/Games/Trivia', [
            'title'    => 'El Poder del Saber',
            'imageUrl' => asset('img/pds.png'),
        ]);
    }
}
