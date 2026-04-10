<?php

namespace App\Http\Requests\Trivia;

use Illuminate\Foundation\Http\FormRequest;

class JoinRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'min:2', 'max:30'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.required' => 'Debes proporcionar un nickname',
            'nickname.min' => 'El nickname debe tener al menos 2 caracteres',
            'nickname.max' => 'El nickname no puede exceder 30 caracteres',
        ];
    }
}
