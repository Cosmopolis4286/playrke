<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $request->route('token'),
            'email' => $request->email,
            'passwordStoreUrl' => route('password.store'),
            'loginUrl' => route('login'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * Responde JSON — el formulario real (useResetPassword.ts) hace un POST
     * por axios y espera un status HTTP claro. Un RedirectResponse aquí
     * (patrón scaffold original de Breeze, pensado para formularios Blade
     * con redirect real) hacía que axios siempre recibiera 200: el toast de
     * "Contraseña restablecida" se mostraba SIEMPRE, incluso con token
     * inválido o expirado — y en ese caso la contraseña nunca se actualizaba
     * en realidad, dejando al usuario creyendo que sí cambió su clave.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
