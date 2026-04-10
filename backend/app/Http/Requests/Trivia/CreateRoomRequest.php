<?php

namespace App\Http\Requests\Trivia;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Implementar policy según necesidad
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'max_players' => ['nullable', 'integer', 'min:2', 'max:1000'],
            'question_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'time_per_question' => ['nullable', 'integer', 'min:5', 'max:300'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'settings' => ['nullable', 'array'],
            'settings.allow_powerups' => ['nullable', 'boolean'],
            'settings.show_leaderboard' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'El título no puede exceder 100 caracteres',
            'max_players.max' => 'Máximo 1000 jugadores permitidos',
            'scheduled_at.after' => 'La fecha debe ser en el futuro',
        ];
    }
}
