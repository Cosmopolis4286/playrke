<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * "Marcar cobrado" de la conciliación: cada cobro agrupa las deudas que el
 * admin vio (settlement_batch_id) y guarda la referencia del pago que hizo el
 * comercio, para poder rastrear después qué transferencia cubrió qué ventas.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::table('platform_receivables', function(Blueprint $table) {
            foreach (['settlement_notes', 'settlement_reference', 'settlement_batch_id'] as $col) {
                if (Schema::hasColumn('platform_receivables', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function up() : void
    {
        Schema::table('platform_receivables', function(Blueprint $table) {
            if (! Schema::hasColumn('platform_receivables', 'settlement_batch_id')) {
                $table->string('settlement_batch_id', 36)->nullable()->after('settled_by')->index()->comment('Cobro que saldó esta deuda');
            }
            if (! Schema::hasColumn('platform_receivables', 'settlement_reference')) {
                $table->string('settlement_reference', 120)->nullable()->after('settlement_batch_id')->comment('Referencia del pago del comercio');
            }
            if (! Schema::hasColumn('platform_receivables', 'settlement_notes')) {
                $table->string('settlement_notes', 255)->nullable()->after('settlement_reference');
            }
        });
    }
};
