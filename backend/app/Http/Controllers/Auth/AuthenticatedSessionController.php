<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Contracts\SettingRepositoryInterface;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
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
    public function create(): Response
    {
        $register = $this->settings->getParameterStatus('REGISTER');

        return Inertia::render('Auth/Login', ['register' => (bool) $register]);
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

        $user = \App\Models\User::with('roles:id,prefix')->select('id')->find(Auth::id());

        // Obtener el rol principal del usuario (primer rol asignado)
        $rolePrefix = $user->roles->first()?->prefix;

        // Crear las variables de sesión
        $request->session()->put('userRole', $rolePrefix);

        // Retornar JSON con datos del usuario para Nuxt
        return response()->json([
            'success' => true,
            // 'user' => [
            //     'id' => (string)$user->id,
            //     'username' => $user->username,
            //     'first_name' => $user->first_name,
            //     'last_name' => $user->last_name,
            //     'email' => $user->email,
            //     'avatar' => $user->avatar ?? null,
            //     'roles' => [$userRole], // Roles como array de strings
            //     'permissions' => [], // TODO: obtener permisos reales
            //     'status' => $user->status ?? 'active',
            //     'email_verified_at' => $user->email_verified_at?->toISOString(),
            //     'created_at' => $user->created_at?->toISOString(),
            //     'updated_at' => $user->updated_at?->toISOString(),
            // ],
            // 'session' => [
            //     'countryCode' => $countryIsoCode,
            //     'currency' => $currency,
            //     'userRole' => $userRole,
            // ]
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
