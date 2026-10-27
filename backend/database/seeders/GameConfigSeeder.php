<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Games\Common\Utils\GameConfig;
use Illuminate\Database\Seeder;

class GameConfigSeeder extends Seeder
{
    public function run() : void
    {
        $games = Game::all(['id', 'name']);

        if ($games->isEmpty()) {
            $this->command->warn('No hay juegos registrados. Seeder omitido.');

            return;
        }

        $configs = [
            ['param' => 'COMMISSION', 'value' => '5', 'description' => 'Comisión del juego (%)'],
        ];

        foreach ($games as $game) {
            foreach ($configs as $config) {
                GameConfig::updateOrCreate(
                    ['game_id' => $game->id, 'param' => $config['param']],
                    ['value' => $config['value'], 'description' => $config['description'], 'status' => 'ACTIVE'],
                );
            }
        }
    }
}
