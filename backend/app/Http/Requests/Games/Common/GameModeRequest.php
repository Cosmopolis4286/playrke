<?php

namespace App\Http\Requests\Games\Common;

use App\Http\Requests\BaseRequest;

class GameModeRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gameId' => ['required', 'exists:games,id'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'gameId.required' => 'El juego es requerido.',
            'gameId.exists' => 'El juego seleccionado no existe.',
            'code.required' => 'El código es requerido.',
            'code.regex' => 'El código solo puede contener letras, números y guiones bajos.',
            'label.required' => 'La etiqueta es requerida.',
        ];
    }
}
