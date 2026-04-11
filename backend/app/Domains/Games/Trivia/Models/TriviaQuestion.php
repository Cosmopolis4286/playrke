<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo: Pregunta de Trivia (trivia_questions).
 * 
 * Las preguntas pertenecen a una sala específica.
 */
class TriviaQuestion extends BaseModel
{
    protected $connection = 'pgsql_trivia';

    protected $table = 'trivia_questions';

    protected $fillable = [
        'room_id',
        'question_number',
        'question_text',
        'image_url',
        'correct_answer',
        'option_1',
        'option_2',
        'option_3',
        'option_4',
        'time_limit_seconds',
        'points',
        'explanation',
        'category',
        'difficulty',
    ];

    protected $casts = [
        'question_number' => 'integer',
        'time_limit_seconds' => 'integer',
        'points' => 'integer',
    ];

    protected $hidden = [
        'correct_answer', // Ocultar en serialización JSON
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(TriviaRoom::class, 'room_id');
    }

    /**
     * Obtener todas las opciones como array.
     * @return array<string>
     */
    public function getOptions(): array
    {
        return array_values(array_filter([
            $this->option_1,
            $this->option_2,
            $this->option_3,
            $this->option_4,
        ]));
    }

    /**
     * Verificar si una respuesta es correcta.
     */
    public function isCorrect(string $answer): bool
    {
        return strtoupper(trim($answer)) === strtoupper(trim($this->correct_answer));
    }
}
