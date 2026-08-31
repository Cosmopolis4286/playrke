<?php

namespace Database\Seeders;

use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use Illuminate\Database\Seeder;

/**
 * Definiciones de ejemplo para desarrollo/QA del Marketplace — no representan
 * un diseño de juego final (valores de metadata a calibrar cuando exista el
 * juego real que los consuma, ej. Cargas/Transportistas vs Cazadores).
 */
class AssetDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'code' => 'HUNTER_RADAR', 'name' => 'Radar de cazador',
                'category' => AssetDefinition::CATEGORY_EQUIPMENT, 'rarity' => 'RARE',
                'stackable' => false, 'tradable' => true, 'transferable' => true,
                'consumable' => false, 'marketplace_enabled' => true, 'game_group' => 'CARGAS',
                'sort_order' => 10,
            ],
            [
                'code' => 'BINGO_BOOSTER_X2', 'name' => 'Booster de premio x2',
                'category' => AssetDefinition::CATEGORY_BOOSTER, 'rarity' => 'COMMON',
                'stackable' => true, 'tradable' => true, 'transferable' => true,
                'consumable' => true, 'marketplace_enabled' => true, 'game_group' => 'BINGO',
                'sort_order' => 20,
            ],
            [
                'code' => 'TRIVIA_HINT_TICKET', 'name' => 'Pista de Trivia',
                'category' => AssetDefinition::CATEGORY_TICKET, 'rarity' => 'COMMON',
                'stackable' => true, 'tradable' => false, 'transferable' => false,
                'consumable' => true, 'marketplace_enabled' => false, 'game_group' => 'TRIVIA',
                'sort_order' => 30,
            ],
        ];

        foreach ($definitions as $definition) {
            AssetDefinition::firstOrCreate(
                ['code' => $definition['code']],
                $definition + ['status' => AssetDefinition::STATUS_ACTIVE],
            );
        }
    }
}
