<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Country;
use App\Models\Currency;
use App\Repositories\CurrencyRepository;
use App\Repositories\CountryRepository;
use App\Contracts\SettingRepositoryInterface;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * @var SettingRepositoryInterface
     */
    protected SettingRepositoryInterface $settings;

    /**
     * Inyecta el repositorio en el constructor
     */
    public function __construct(SettingRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    // /**
    //  * Display the login view.
    //  * Ya no se usa - Nuxt maneja las vistas
    //  */
    // public function create(): JsonResponse
    // {
    //     $register = (new SettingRepository(new Setting()))->getParameterStatus('REGISTER');

    //     return response()->json([
    //         'register' => (bool)$register,
    //         'loginUrl' => route('login'),
    //         'passwordRequestUrl' => route('password.request'),
    //         'registerUrl' => route('register'),
    //     ]);
    // }

    /**
     * Página de inicio / login con estado de registro
     */
    public function create(): Response
    {
        $register = $this->settings->getParameterStatus('REGISTER');

        return Inertia::render('Auth/Login', [
            'inertiaOnly' => true,
            // 'register' => (bool) $register,
            // 'loginUrl' => route('login'),
            // 'passwordRequestUrl' => route('password.request'),
            // 'registerUrl' => route('register'),
            // 'html' => View::make('auth.login', [
            //     'register' => $register,
            // ])->render(),
        ]);
    }

    /**
     * Maneja el login del usuario y retorna JSON para Nuxt.
     *
     * @param \App\Http\Requests\LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(LoginRequest $request): JsonResponse
    {
        // Autenticar al usuario
        $request->authenticate();

        // Regenerar la sesión para evitar ataques de fijación de sesión
        $request->session()->regenerate();

        $user = Auth::user();

        // Obtener el país del usuario
        $countryId = $user->country_id;

        // Obtener el rol principal del usuario (primer rol asignado)
        $firstRole = $user->roles->first();

        $userRole = null;
        if ($firstRole !== null) {
            // Obtener el prefijo del rol directamente
            $userRole = $firstRole->prefix;
        }

        // Obtener el código ISO del país
        $countryIsoCode = (new CountryRepository(new Country()))->find($countryId, ['iso_code_2'])?->iso_code_2;

        // Obtener el ID de la moneda asociada al país y encriptarlo
        $currency = encrypt((new CurrencyRepository(new Currency()))->getValueByCountry($countryId, 'id'));

        // Crear las variables de sesión
        $request->session()->put('countryCode', $countryIsoCode);
        $request->session()->put('currency', $currency);
        $request->session()->put('userRole', $userRole);

        // Retornar JSON con datos del usuario para Nuxt
        return response()->json([
            'success' => true,
            'user' => [
                'id' => (string)$user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'avatar' => $user->avatar ?? null,
                'roles' => [$userRole], // Roles como array de strings
                'permissions' => [], // TODO: obtener permisos reales
                'status' => $user->status ?? 'active',
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'created_at' => $user->created_at?->toISOString(),
                'updated_at' => $user->updated_at?->toISOString(),
            ],
            'session' => [
                'countryCode' => $countryIsoCode,
                'currency' => $currency,
                'userRole' => $userRole,
            ]
        ]);
    }

    /**
     * Destroy an authenticated session.
     * Retorna JSON para Nuxt frontend.
     */
    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }
}
