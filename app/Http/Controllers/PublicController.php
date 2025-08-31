<?php

namespace App\Http\Controllers;

use App\Models\Settings\Settings;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de páginas públicas
 *
 * Maneja la página de login y páginas de información estática.
 */
class PublicController extends Controller
{
    /**
     * Página de inicio / login con estado de registro
     *
     * @return Response
     */
    public function root(): Response
    {
        $register = Settings::getStatus('REGISTER');

        // Renderiza la página Login como componente Vue/Inertia
        return Inertia::render('Auth/Login', [
            'register' => $register,
            'passwordRequestUrl' => route('password.request'),
        ]);
    }

    /**
     * Páginas de información estática (terms, about, privacy)
     *
     * @param string $page
     * @return Response
     */
    public function info(string $page): Response
    {
        $allowed = ['terms', 'about', 'privacy'];

        if (!in_array($page, $allowed)) {
            abort(404);
        }

        // Renderiza página estática usando componente Vue/Inertia
        return Inertia::render('Info/StaticPage', [
            'page' => $page,
        ]);
    }
}
