<?php

namespace Database\Seeders;

use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Payments\Models\PaymentProduct;
use Illuminate\Database\Seeder;

/**
 * Packs de ejemplo comprables con USDT-TRC20, para poder probar el flujo
 * completo de punta a punta en desarrollo/staging. Precios y cantidades son
 * valores de ejemplo — a calibrar cuando exista un catálogo comercial real.
 * Requiere que AssetDefinitionSeeder haya corrido antes (TRIVIA_HINT_TICKET).
 */
class PaymentProductSeeder extends Seeder
{
    public function run() : void
    {
        $hintTicket = AssetDefinition::where('code', 'TRIVIA_HINT_TICKET')->first();

        if (! $hintTicket) {
            $this->command?->warn('PaymentProductSeeder: TRIVIA_HINT_TICKET no existe — corre AssetDefinitionSeeder primero.');

            return;
        }

        $products = [
            [
                'code' => 'HINTS_PACK_SMALL', 'name' => 'Pack de 5 pistas de Trivia',
                'asset_definition_id' => $hintTicket->id, 'asset_quantity' => 5,
                'price_usdt' => 1.0,
            ],
            [
                'code' => 'HINTS_PACK_LARGE', 'name' => 'Pack de 20 pistas de Trivia',
                'asset_definition_id' => $hintTicket->id, 'asset_quantity' => 20,
                'price_usdt' => 3.5,
            ],
        ];

        foreach ($products as $product) {
            PaymentProduct::firstOrCreate(
                ['code' => $product['code']],
                $product + ['status' => PaymentProduct::STATUS_ACTIVE],
            );
        }
    }
}
