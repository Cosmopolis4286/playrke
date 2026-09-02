<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];

        // Turnstile solo entra en juego tras intentos fallidos previos (misma
        // key que el lockout de abajo) — no se exige en el primer login
        // legítimo, evita fricción para el caso común y solo protege contra
        // fuerza bruta sostenida / credential stuffing.
        if (RateLimiter::attempts($this->throttleKey()) >= 2) {
            // 'required' solo si hay secret key configurada — mismo criterio
            // que RegisterRequest (CloudflareTurnstile ya falla abierto sin
            // ella, pero 'required' bloqueaba el campo vacío igual).
            $rules['cf-turnstile-response'] = [
                config('services.turnstile.secret_key') ? 'required' : 'nullable',
                new \App\Rules\CloudflareTurnstile($this->ip()),
            ];
        }

        return $rules;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * La contraseña se valida ANTES de revelar cualquier cosa sobre el
     * estado de la cuenta (existe/no existe, activa/suspendida/pendiente):
     * antes, un 'login' que coincidía con un email/username real recibía un
     * mensaje distinto a uno inexistente sin haber probado ninguna
     * contraseña — enumeración de cuentas explotable con solo una lista de
     * emails, sin necesidad de contraseñas válidas. Ahora Auth::attempt()
     * decide primero; el estado de la cuenta solo se expone al dueño real
     * (quien ya demostró conocer la contraseña correcta).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = $this->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $login, 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->is_system_account || !$user->isActive()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            $message = match (true) {
                $user->is_system_account => 'Esta cuenta no puede iniciar sesión.',
                $user->status === 'INACTIVE' => trans('auth.inactive'),
                $user->status === 'SUSPENDED' => trans('auth.suspended'),
                $user->status === 'PENDING_VERIFICATION' => trans('auth.pending_verification'),
                default => trans('auth.account_disabled'),
            };

            throw ValidationException::withMessages([
                'login' => $message,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')) . '|' . $this->ip());
    }
}
