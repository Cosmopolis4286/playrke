<?php

namespace Database\Seeders\Trivia;

use App\Domains\Trivia\Models\TriviaQuestion;
use App\Domains\Trivia\Models\TriviaRoom;
use Illuminate\Database\Seeder;

class TriviaRoomSeeder extends Seeder
{
    public function run() : void
    {
        // Crear sala de ejemplo
        $room = TriviaRoom::create([
            'code' => 'DEMO01',
            'title' => 'Sala de Prueba - Cultura General',
            'description' => 'Una sala para probar el sistema de trivia',
            'status' => 'waiting',
            'max_players' => 50,
            'question_count' => 5,
            'time_per_question' => 15,
            'settings' => [
                'show_leaderboard' => true,
                'allow_powerups' => false,
            ],
        ]);

        // Crear preguntas de ejemplo
        $questions = [
            [
                'question_text' => '¿Cuál es la capital de Francia?',
                'correct_answer' => 'París',
                'option_1' => 'París',
                'option_2' => 'Londres',
                'option_3' => 'Berlín',
                'option_4' => 'Madrid',
                'category' => 'Geografía',
                'difficulty' => 'easy',
            ],
            [
                'question_text' => '¿En qué año llegó el hombre a la luna?',
                'correct_answer' => '1969',
                'option_1' => '1965',
                'option_2' => '1969',
                'option_3' => '1972',
                'option_4' => '1959',
                'category' => 'Historia',
                'difficulty' => 'medium',
            ],
            [
                'question_text' => '¿Cuál es el océano más grande del mundo?',
                'correct_answer' => 'Pacífico',
                'option_1' => 'Atlántico',
                'option_2' => 'Índico',
                'option_3' => 'Pacífico',
                'option_4' => 'Ártico',
                'category' => 'Geografía',
                'difficulty' => 'easy',
            ],
            [
                'question_text' => '¿Quién pintó la Mona Lisa?',
                'correct_answer' => 'Leonardo da Vinci',
                'option_1' => 'Miguel Ángel',
                'option_2' => 'Leonardo da Vinci',
                'option_3' => 'Pablo Picasso',
                'option_4' => 'Vincent van Gogh',
                'category' => 'Arte',
                'difficulty' => 'easy',
            ],
            [
                'question_text' => '¿Cuál es el elemento químico más abundante en el universo?',
                'correct_answer' => 'Hidrógeno',
                'option_1' => 'Oxígeno',
                'option_2' => 'Carbono',
                'option_3' => 'Hidrógeno',
                'option_4' => 'Helio',
                'category' => 'Ciencia',
                'difficulty' => 'medium',
            ],
        ];

        foreach ($questions as $index => $qData) {
            TriviaQuestion::create(array_merge($qData, [
                'room_id' => $room->id,
                'question_number' => $index + 1,
                'points' => 100,
                'time_limit_seconds' => 15,
            ]));
        }

        $this->command->info("Sala de trivia '{$room->code}' creada con " . count($questions) . ' preguntas.');
    }
}
