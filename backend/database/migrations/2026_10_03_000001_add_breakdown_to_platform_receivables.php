<?php

declare(strict_types=1);

use App\Models\MovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deuda del comercio por ventas fuera del sistema = RKE + aporte al jackpot +
 * parte del bote (antes solo RKE). El comercio cobra el cartón en efectivo y
 * retiene todo; la plataforma es quien acumula el jackpot y paga los premios a
 * billeteras, así que necesita recibir esas partes. Las comisiones de la red
 * (vendedor, agencia, comercializadora) y la del creador no forman parte.
 *
 * Columnas ya en el Schema::create consolidado (instalaciones nuevas); esta
 * migración las agrega a BDs existentes, protegida con hasColumn. Las deudas
 * previas eran solo de RKE: su desglose se completa con rke_amount = amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_receivables', function (Blueprint $table) {
            if (!Schema::hasColumn('platform_receivables', 'rke_amount')) {
                $table->decimal('rke_amount', 13, 2)->default(0)->after('amount')->comment('Comisión RKE de la plataforma');
            }
            if (!Schema::hasColumn('platform_receivables', 'jackpot_amount')) {
                $table->decimal('jackpot_amount', 13, 2)->default(0)->after('rke_amount')->comment('Aporte al jackpot (lo acumula la plataforma)');
            }
            if (!Schema::hasColumn('platform_receivables', 'prize_pool_amount')) {
                $table->decimal('prize_pool_amount', 13, 2)->default(0)->after('jackpot_amount')->comment('Parte del bote (la plataforma paga los premios a billeteras)');
            }
        });

        // Deudas previas: eran solo RKE.
        \Illuminate\Support\Facades\DB::table('platform_receivables')
            ->where('rke_amount', 0)->where('jackpot_amount', 0)->where('prize_pool_amount', 0)
            ->update(['rke_amount' => \Illuminate\Support\Facades\DB::raw('amount')]);

        foreach ([
            ['code' => 'BINGO_POOL_FUNDING', 'label' => 'Fondeo de bote/jackpot (cobro a comercio)', 'icon' => 'fas fa-piggy-bank', 'color' => '#0891b2', 'is_credit' => true, 'sort_order' => 67],
            ['code' => 'BINGO_POOL_FUNDING_REVERSAL', 'label' => 'Devolución de fondeo de bote/jackpot', 'icon' => 'fas fa-rotate-left', 'color' => '#b91c1c', 'is_credit' => false, 'sort_order' => 68],
        ] as $type) {
            MovementType::firstOrCreate(['code' => $type['code']], $type + [
                'requires_approval' => false,
                'game_group'        => 'BINGO',
                'visible_roles'     => ['admin', 'superadmin'],
                'is_system'         => true,
                'status'            => 'ACTIVE',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('platform_receivables', function (Blueprint $table) {
            foreach (['prize_pool_amount', 'jackpot_amount', 'rke_amount'] as $col) {
                if (Schema::hasColumn('platform_receivables', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        MovementType::whereIn('code', ['BINGO_POOL_FUNDING', 'BINGO_POOL_FUNDING_REVERSAL'])
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('wallet_movements')->whereColumn('wallet_movements.type', 'movement_types.code'))
            ->delete();
    }
};
