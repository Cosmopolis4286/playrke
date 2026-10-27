<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

use App\Models\BaseModel;

/**
 * Modelo: Pregunta del Banco de Trivia (trivia_questions).
 *
 * Banco global de preguntas — NO pertenece a una sala específica.
 * El vínculo sala↔pregunta se gestiona en trivia_game_questions.
 *
 * Columnas reales (ver migración 2026_04_09_02):
 *   id                  ulid PK
 *   question_text       text
 *   image_url           string nullable
 *   correct_option_index tinyint (0-based, índice de options[])
 *   options             jsonb  ["opción A","opción B","opción C","opción D"]
 *   time_limit_seconds  uint   default 15
 *   points              uint   default 100
 *   is_active           bool   default true
 *   times_used          uint   default 0
 *   explanation         text nullable
 *   category            string nullable
 *   difficulty          string default 'medium'  (easy|medium|hard)
 *   created_at / updated_at / deleted_at
 */
class TriviaQuestion extends BaseModel
{
    protected $casts = [
        'options' => 'array',
        'correct_option_index' => 'integer',
        'time_limit_seconds' => 'integer',
        'points' => 'integer',
        'times_used' => 'integer',
        'is_active' => 'boolean',
    ];
    protected $connection = 'pgsql_trivia';

    protected $fillable = [
        'question_text',
        'image_url',
        'correct_option_index',
        'options',
        'time_limit_seconds',
        'points',
        'is_active',
        'explanation',
        'category',
        'difficulty',
    ];

    protected $hidden = [
        'correct_option_index', // no se expone al jugador durante el juego
    ];

    protected $table = 'trivia_questions';

    /**
     * Obtener la opción correcta como texto.
     */
    public function correctOptionText() : ?string
    {
        return $this->options[$this->correct_option_index] ?? null;
    }

    /**
     * Verificar si el índice de opción enviado es correcto.
     */
    public function isCorrect(int $optionIndex) : bool
    {
        return $optionIndex === $this->correct_option_index;
    }

    /**
     * Scope: Solo preguntas activas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filtrar por categoría.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filtrar por dificultad.
     */
    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }
}
