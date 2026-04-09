<?php

namespace App\Http\Requests\Trivia;

use Illuminate\Foundation\Http\FormRequest;

class StartGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'player_ids' => ['required', 'array', 'min:1'],
            'player_ids.*' => ['string'], // IDs de jugadores
        ];
    }

    public function messages(): array
    {
        return [
            'player_ids.required' => 'Se necesita al menos un jugador para iniciar',
            'player_ids.min' => 'Se necesita al menos un jugador para iniciar',
        ];
    }
}
