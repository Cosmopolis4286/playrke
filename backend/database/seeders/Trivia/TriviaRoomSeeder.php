<?php

namespace Database\Seeders\Trivia;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Salas de demostración gratuitas para desarrollo local. Las preguntas viven
 * en el banco global (TriviaQuestionSeeder): cada partida las toma de ahí
 * filtrando por la categoría de la sala, así que aquí no se crean preguntas.
 */
class TriviaRoomSeeder extends Seeder
{
    public function run() : void
    {
        $freeForAllModeId = DB::connection('mysql')
            ->table('game_modes')
            ->where('code', 'FREE_FOR_ALL')
            ->value('id');

        $rooms = [
            ['code' => 'DEMO01', 'title' => 'Cultura General', 'category' => null],
            ['code' => 'DEMO02', 'title' => 'Geografía Mundial', 'category' => 'Geografía'],
            ['code' => 'DEMO03', 'title' => 'Ciencia Rápida', 'category' => 'Ciencia'],
        ];

        foreach ($rooms as $data) {
            TriviaRoom::firstOrCreate(['code' => $data['code']], [
                'title' => $data['title'],
                'description' => 'Sala de demostración gratuita',
                'category' => $data['category'],
                'host_id' => '1',
                'status' => 'waiting',
                'game_mode_id' => $freeForAllModeId ? (string) $freeForAllModeId : null,
                'max_players' => 50,
                'question_count' => 5,
                'time_per_question' => 20,
                'settings' => ['show_leaderboard' => true],
            ]);
        }

        $this->command?->info('Salas de trivia de demostración: ' . implode(', ', array_column($rooms, 'code')) . '.');
    }
}
