<?php

namespace Database\Seeders;

use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Payments\Models\PaymentProduct;
use Illuminate\Database\Seeder;

/**
 * Productos de ejemplo para desarrollo/QA — precios a calibrar con negocio
 * antes de producción. Reutiliza AssetDefinition ya sembrado por
 * AssetDefinitionSeeder (ver PAYMENTS_USDT_TRC20_DESIGN.md sección 1: este
 * módulo nunca duplica el catálogo de Inventory).
 */
class PaymentProductSeeder extends Seeder
{
    public function run(): void
    {
        $hintTicket = AssetDefinition::where('code', 'TRIVIA_HINT_TICKET')->first();

        if (!$hintTicket) {
            return;
        }

        PaymentProduct::firstOrCreate(
            ['code' => 'TRIVIA_HINT_PACK_5'],
            [
                'name' => 'Pack de 5 pistas de Trivia',
                'asset_definition_id' => $hintTicket->id,
                'asset_quantity' => 5,
                'price_usdt' => 1.00,
                'status' => PaymentProduct::STATUS_ACTIVE,
            ],
        );
    }
}
