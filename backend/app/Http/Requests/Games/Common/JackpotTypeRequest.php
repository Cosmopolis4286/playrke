<?php

namespace App\Http\Requests\Games\Common;

use App\Http\Requests\BaseRequest;

class JackpotTypeRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => ['required', 'exists:games,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:50'],
            'periodicity' => ['nullable', 'in:PER_ROOM,WEEKLY,BIWEEKLY,MONTHLY'],
            'status' => ['nullable', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'game_id.required' => 'El juego es requerido.',
            'game_id.exists' => 'El juego seleccionado no existe.',
            'currency_id.required' => 'La moneda es requerida.',
            'currency_id.exists' => 'La moneda seleccionada no existe.',
            'code.required' => 'El código es requerido.',
            'code.regex' => 'El código solo puede contener letras, números y guiones bajos.',
            'label.required' => 'La etiqueta es requerida.',
            'periodicity.in' => 'La periodicidad seleccionada no es válida.',
        ];
    }
}
