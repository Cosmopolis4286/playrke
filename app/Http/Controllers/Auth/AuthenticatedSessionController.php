<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Coin;
use App\Models\Country;
use App\Models\Settings;
use App\Models\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $register = Settings::getStatus('REGISTER');

        return view('auth.login', compact('register'));
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
        $userType = UserType::getValueById(Auth::user()->user_type, 'prefix');

        $isoCode = Country::getCountryValue($country, 'iso_code_2');
        $coin = encrypt(Coin::getValueByCountry($country, 'id'));

        // Crear la variable de sesión 'countryCode'
        $request->session()->put('countryCode', $isoCode);

        // Crear la variable de sesión 'coin'
        $request->session()->put('coin', $coin);

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
