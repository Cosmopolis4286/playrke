<?php

declare(strict_types=1);

use App\Models\MovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para revertir exactamente una venta de Bingo cuando su lote se
 * reembolsa (sala cancelada o pago rechazado — ver BingoLotSaleReversalService):
 *
 *  - bingo_card_lots.financial_breakdown: desglose de lo que la venta sumó en
 *    sala, BingoCreatorSale, wallet RKE y jackpot.
 *  - bingo_creator_sales.jackpot_contribution: el aporte al jackpot deja de
 *    mezclarse dentro de prize_pool_amount.
 *  - bingo_creator_sales: rke/room/sale/seller a decimal(13,2) — cada fila
 *    acumula todas las ventas de una sala por moneda y decimal(6,2) desbordaba.
 *  - movement_types RKE_COMMISSION_REVERSAL (FK de wallet_movements.type):
 *    producción solo corre `migrate --force`, no los seeders.
 *
 * Las columnas ya están en los Schema::create consolidados (instalaciones
 * nuevas); esta migración las agrega a BDs existentes, protegida con hasColumn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_card_lots', function (Blueprint $table) {
            if (!Schema::hasColumn('bingo_card_lots', 'financial_breakdown')) {
                $table->json('financial_breakdown')->nullable()->after('has_custom_cards');
            }
        });

        Schema::table('bingo_creator_sales', function (Blueprint $table) {
            $table->decimal('sale_amount', 13, 2)->change();
            $table->decimal('rke_commission', 13, 2)->change();
            $table->decimal('room_commission', 13, 2)->change();
            $table->decimal('seller_commission', 13, 2)->nullable()->change();

            if (!Schema::hasColumn('bingo_creator_sales', 'jackpot_contribution')) {
                $table->decimal('jackpot_contribution', 13, 2)->default(0)->after('seller_commission')->comment('Aporte al jackpot');
            }
        });

        MovementType::firstOrCreate(
            ['code' => 'RKE_COMMISSION_REVERSAL'],
            [
                'label'             => 'Reversión comisión RKE',
                'icon'              => 'fas fa-rotate-left',
                'color'             => '#b91c1c',
                'is_credit'         => false,
                'requires_approval' => false,
                'game_group'        => null,
                'sort_order'        => 65,
                'visible_roles'     => ['admin', 'superadmin'],
                'is_system'         => true,
                'status'            => 'ACTIVE',
            ],
        );
    }

    public function down(): void
    {
        Schema::table('bingo_card_lots', function (Blueprint $table) {
            if (Schema::hasColumn('bingo_card_lots', 'financial_breakdown')) {
                $table->dropColumn('financial_breakdown');
            }
        });

        Schema::table('bingo_creator_sales', function (Blueprint $table) {
            if (Schema::hasColumn('bingo_creator_sales', 'jackpot_contribution')) {
                $table->dropColumn('jackpot_contribution');
            }
        });

        // Los anchos de columna no se reducen (podría truncar montos existentes)
        // y el tipo de movimiento se conserva si ya tiene movimientos asociados.
        MovementType::where('code', 'RKE_COMMISSION_REVERSAL')
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('wallet_movements')->whereColumn('wallet_movements.type', 'movement_types.code'))
            ->delete();
    }
};
