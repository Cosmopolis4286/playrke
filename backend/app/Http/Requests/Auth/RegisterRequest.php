<?php

namespace App\Http\Requests\Auth;

use App\Rules\CloudflareTurnstile;
use App\Rules\ValidPhoneForCountry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
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
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            // ValidPhoneForCountry no bloquea si el país no tiene patrón
            // configurado (ver PhonePatterns) — mismo criterio que el resto
            // de FormRequests que ya usan esta regla.
            'phone' => ['nullable', 'string', 'max:20', new ValidPhoneForCountry($this->input('country'))],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
            // 'required' solo si hay secret key configurada — CloudflareTurnstile
            // ya falla abierto sin ella (dev/test sin cuenta Cloudflare), pero
            // 'required' bloqueaba el campo vacío igual, anulando ese fail-open.
            'cf-turnstile-response' => [
                config('services.turnstile.secret_key') ? 'required' : 'nullable',
                new CloudflareTurnstile($this->ip()),
            ],
        ];
    }
}
