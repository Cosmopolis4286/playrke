<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reparto de comisión en cascada de la red comercial (Comercializadora →
 * Agencia → Vendedor, ver CommissionCalculationService::networkSplit()).
 *
 * Las columnas ya están en los Schema::create consolidados de bingo
 * (instalaciones nuevas); esta migración las agrega a BDs existentes. Cada
 * columna se protege con hasColumn() para que sea no-op donde ya existan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('bingo_rooms', 'subsidiary_commission')) {
                $table->decimal('subsidiary_commission', 8, 2)->default(0)->after('seller_commission')->comment('Comisión de agencias (override sobre el vendedor)');
            }
            if (!Schema::hasColumn('bingo_rooms', 'commerce_commission')) {
                $table->decimal('commerce_commission', 8, 2)->default(0)->after('subsidiary_commission')->comment('Comisión de comercializadoras (override de red)');
            }
        });

        Schema::table('bingo_creator_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('bingo_creator_sales', 'subsidiary_commission')) {
                $table->decimal('subsidiary_commission', 13, 2)->default(0)->after('seller_commission')->comment('Comisión de agencias (override sobre el vendedor)');
            }
            if (!Schema::hasColumn('bingo_creator_sales', 'commerce_commission')) {
                $table->decimal('commerce_commission', 13, 2)->default(0)->after('subsidiary_commission')->comment('Comisión de comercializadoras (override de red)');
            }
        });

        Schema::table('bingo_card_lots', function (Blueprint $table) {
            if (!Schema::hasColumn('bingo_card_lots', 'subsidiary_id')) {
                $table->unsignedBigInteger('subsidiary_id')->nullable()->after('commission_amount')->comment('Agencia del vendedor al vender (snapshot)');
                $table->index(['subsidiary_id', 'status'], 'idx_bingo_card_lots_subsidiary_status');
            }
            if (!Schema::hasColumn('bingo_card_lots', 'commerce_id')) {
                $table->unsignedBigInteger('commerce_id')->nullable()->after('subsidiary_id')->comment('Comercializadora del vendedor al vender (snapshot)');
                $table->index(['commerce_id', 'status'], 'idx_bingo_card_lots_commerce_status');
            }
            if (!Schema::hasColumn('bingo_card_lots', 'subsidiary_commission_amount')) {
                $table->decimal('subsidiary_commission_amount', 10, 2)->default(0)->after('commerce_id')->comment('Comisión de la agencia (Y − X)');
            }
            if (!Schema::hasColumn('bingo_card_lots', 'commerce_commission_amount')) {
                $table->decimal('commerce_commission_amount', 10, 2)->default(0)->after('subsidiary_commission_amount')->comment('Comisión de la comercializadora (Z − max(X, Y))');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bingo_card_lots', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_card_lots_subsidiary_status');
            $table->dropIndex('idx_bingo_card_lots_commerce_status');
            $table->dropColumn(['subsidiary_id', 'commerce_id', 'subsidiary_commission_amount', 'commerce_commission_amount']);
        });

        Schema::table('bingo_creator_sales', function (Blueprint $table) {
            $table->dropColumn(['subsidiary_commission', 'commerce_commission']);
        });

        Schema::table('bingo_rooms', function (Blueprint $table) {
            $table->dropColumn(['subsidiary_commission', 'commerce_commission']);
        });
    }
};
