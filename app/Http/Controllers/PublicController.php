<?php

namespace App\Http\Controllers;

use App\Models\Settings;

class PublicController extends Controller
{
    /**
     * Página de inicio / login con estado de registro
     */
    public function root()
    {
        $register = Settings::getStatus('REGISTER');
        return view('auth.login', compact('register'));
    }

    /**
     * Páginas de información estática
     */
    public function info($page)
    {
        $allowed = ['terms', 'about', 'privacy'];

        if (!in_array($page, $allowed)) {
            abort(404);
        }

        return view("includes.info.$page");
    }
}
