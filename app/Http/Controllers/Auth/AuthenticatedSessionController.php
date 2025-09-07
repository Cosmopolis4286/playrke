<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Settings\Settings;
use App\Repositories\Currencies\CurrencyRepository;
use App\Repositories\Countries\CountryRepository;
use App\Repositories\User\Roles\RoleRepository;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $register = Settings::getStatus('REGISTER');

        return Inertia::render('Auth/Login', [
            'register' => $register,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Autenticar al usuario
        $request->authenticate();

        // Regenerar la sesión para evitar ataques de fijación de sesión
        $request->session()->regenerate();

        $country = Auth::user()->country;
        $userType = RoleRepository::getValueById(Auth::user()->user_type, 'prefix');

        $isoCode = CountryRepository::getValue($country, 'iso_code_2');
        $currency = encrypt(CurrencyRepository::getValueByCountry($country, 'id'));

        // Crear la variable de sesión 'countryCode'
        $request->session()->put('countryCode', $isoCode);

        // Crear la variable de sesión 'currency'
        $request->session()->put('currency', $currency);

        // Crear la variable de sesión 'userType'
        $request->session()->put('userType', $userType);

        // Redirigir al usuario a la ruta de destino
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
