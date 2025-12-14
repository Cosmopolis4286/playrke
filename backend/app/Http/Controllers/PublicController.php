<?php

namespace App\Http\Controllers;

use App\Repositories\Settings\Contracts\SettingRepositoryContract;
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
     * @var SettingRepositoryContract
     */
    protected SettingRepositoryContract $settings;

    /**
     * Inyecta el repositorio en el constructor
     */
    public function __construct(SettingRepositoryContract $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Página de inicio / login con estado de registro
     */
    public function root(): Response
    {
        $register = $this->settings->getParameterStatus('REGISTER');

        return Inertia::render('auth/LoginLegacy', [
            'register' => (bool) $register,
            'loginUrl' => route('login'),
            'passwordRequestUrl' => route('password.request'),
            'registerUrl' => route('register'),

            // Blade renderizado
            'html' => View::make('auth.login', [
                'register' => $register,
            ])->render(),
        ]);
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
