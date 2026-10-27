<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Game;
use App\Models\Games\Common\Jackpot\JackpotType;
use Illuminate\Database\Seeder;

class JackpotTypeSeeder extends Seeder
{
    public function run() : void
    {
        $typesByGame = [
            'BINGO' => [
                ['code' => 'WEEKLY_USDT',  'label' => 'Jackpot Semanal USDT',  'currency' => 'USDT', 'periodicity' => JackpotType::PERIODICITY_WEEKLY],
                ['code' => 'MONTHLY_USDT', 'label' => 'Jackpot Mensual USDT',  'currency' => 'USDT', 'periodicity' => JackpotType::PERIODICITY_MONTHLY],
                ['code' => 'WEEKLY_VES',   'label' => 'Jackpot Semanal Bs',    'currency' => 'VES',  'periodicity' => JackpotType::PERIODICITY_WEEKLY],
            ],
        ];

        foreach ($typesByGame as $gameName => $types) {
            $gameId = Game::where('name', $gameName)->value('id');
            if (! $gameId) {
                $this->command->warn("Juego '{$gameName}' no encontrado. Tipos de jackpot omitidos.");

                continue;
            }

            foreach ($types as $type) {
                $currencyId = Currency::where('iso_code', $type['currency'])->value('id');
                if (! $currencyId) {
                    $this->command->warn("Moneda '{$type['currency']}' no encontrada. Jackpot '{$type['code']}' omitido.");

                    continue;
                }

                JackpotType::updateOrCreate(
                    ['game_id' => $gameId, 'code' => $type['code']],
                    [
                        'currency_id' => $currencyId,
                        'label' => $type['label'],
                        'periodicity' => $type['periodicity'],
                        'status' => 'ACTIVE',
                    ],
                );
            }
        }
    }
}
