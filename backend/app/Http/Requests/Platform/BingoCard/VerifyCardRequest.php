<?php

namespace App\Http\Requests\Platform\BingoCard;

use App\Rules\CloudflareTurnstile;
use Illuminate\Foundation\Http\FormRequest;

class VerifyCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial_number' => ['required', 'string', 'max:50'],
            'phone_last4'   => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
            'cf-turnstile-response' => ['required', new CloudflareTurnstile($this->ip())],
        ];
    }
}
