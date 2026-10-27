<?php

namespace App\Http\Requests\Games\Common;

use App\Http\Requests\BaseRequest;

class GameModeRequest extends BaseRequest
{
    public function authorize() : bool
    {
        return true;
    }

    public function messages() : array
    {
        return [
            'game_id.required' => 'El juego es requerido.',
            'game_id.exists' => 'El juego seleccionado no existe.',
            'code.required' => 'El código es requerido.',
            'code.regex' => 'El código solo puede contener letras, números y guiones bajos.',
            'label.required' => 'La etiqueta es requerida.',
        ];
    }

    public function rules() : array
    {
        return [
            'game_id' => ['required', 'exists:games,id'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:50'],
            'status' => ['nullable', 'in:ACTIVE,INACTIVE'],
        ];
    }
}
