<?php

namespace App\Http\Controllers;

use App\Contracts\SettingRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\View;

/**
 * Controlador de páginas públicas
 *
 * Maneja la página de login y páginas de información estática.
 */
class PublicController extends Controller
{
    /**
     * Inyecta el repositorio en el constructor
     */
    public function __construct(
        protected SettingRepositoryInterface $settings
    ) {}

    /**
     * Página de inicio / login con estado de registro
     */
    public function root(): Response
    {
        $register = $this->settings->getParameterStatus('REGISTER');

        return Inertia::render('Auth/Login', [
            'inertiaOnly' => true,
            'register' => (bool) $register,
        ]);
    }

    /**
     * Página pública "Verificar mi cartón" — buscador por serial + teléfono,
     * sin sesión (ver App\Http\Controllers\Platform\BingoCardVerifyController).
     */
    public function verifyCard(): Response
    {
        return Inertia::render('Public/VerifyCard');
    }

    /**
     * Páginas de información estática (terms, about, privacy)
     */
    public function info(string $page): Response
    {
        $allowed = ['terms', 'about', 'privacy'];

        if (!in_array($page, $allowed)) {
            abort(404);
        }

        return Inertia::render('Info/StaticPage', [
            'page' => $page,
        ]);
    }
}
