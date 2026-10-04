<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Venta cruzada: un comercio vende cartones de una sala PÚBLICA creada por
 * otro comercio (o por la plataforma) y retiene en efectivo la comisión del
 * creador. Esa parte pasa a formar parte de su deuda con la plataforma, que la
 * custodia y se la paga al creador cuando la sala cerró y la deuda está cobrada.
 *
 * Columnas ya en el Schema::create consolidado (instalaciones nuevas); esta
 * migración las agrega a BDs existentes, protegida con hasColumn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_receivables', function (Blueprint $table) {
            if (!Schema::hasColumn('platform_receivables', 'creator_amount')) {
                $table->decimal('creator_amount', 13, 2)->default(0)->after('prize_pool_amount')->comment('Comisión del creador de la sala (venta cruzada)');
            }
            if (!Schema::hasColumn('platform_receivables', 'creator_payee_user_id')) {
                $table->foreignSnowflake('creator_payee_user_id')->nullable()->after('creator_amount')
                    ->constrained('users')->nullOnDelete()->cascadeOnUpdate()
                    ->comment('Creador de la sala que debe recibir creator_amount');
            }
            if (!Schema::hasColumn('platform_receivables', 'creator_paid_at')) {
                $table->timestamp('creator_paid_at')->nullable()->after('creator_payee_user_id')->comment('Cuándo se le pagó al creador');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_receivables', function (Blueprint $table) {
            if (Schema::hasColumn('platform_receivables', 'creator_payee_user_id')) {
                $table->dropConstrainedForeignId('creator_payee_user_id');
            }
            foreach (['creator_paid_at', 'creator_amount'] as $col) {
                if (Schema::hasColumn('platform_receivables', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
