<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\LoginRequest;
use App\Repositories\Currencies\CurrencyRepository;
use App\Repositories\Countries\CountryRepository;
use App\Repositories\Settings\SettingRepository;
use App\Repositories\User\Roles\RoleRepository;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\{RedirectResponse, Request, JsonResponse};
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $register = (new SettingRepository())->getParameterStatus('REGISTER');

        return Inertia::render('Auth/Login', [
            'register' => (bool)$register,  // Forzamos booleano
            'loginUrl' => route('login'),   // URL POST para login
            'passwordRequestUrl' => route('password.request'), // recuperación de contraseña
            'registerUrl' => route('register'), // URL para registro
        ]);
    }

    /**
     * Maneja el login del usuario y configura variables de sesión.
     *
     * @param \App\Http\Requests\LoginRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Autenticar al usuario
        $request->authenticate();

        // Regenerar la sesión para evitar ataques de fijación de sesión
        $request->session()->regenerate();

        $user = Auth::user();

        // Obtener el país del usuario
        $countryId = $user->country_id;

        // Obtener el rol principal del usuario (primer rol asignado)
        $firstRoleId = $user->roles->first()?->id;

        $userRole = null;
        if ($firstRoleId !== null) {
            // Obtener el prefijo del rol usando el repositorio
            $userRole = RoleRepository::getValueById($firstRoleId, 'prefix');
        }

        // Obtener el código ISO del país
        $countryIsoCode = (new CountryRepository())->find($countryId, ['iso_code_2'])?->iso_code_2;

        // Obtener el ID de la moneda asociada al país y encriptarlo
        $currency = encrypt((new CurrencyRepository())->getValueByCountry($countryId, 'id'));

        // Crear las variables de sesión
        $request->session()->put('countryCode', $countryIsoCode);
        $request->session()->put('currency', $currency);
        $request->session()->put('userRole', $userRole);

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

        // Si es una petición AJAX/Inertia, retornar respuesta JSON
        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente',
                'redirect' => route('login')
            ]);
        }

        // Para peticiones normales, redirigir al login con Inertia
        return redirect()->route('login');
    }
}
