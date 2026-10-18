<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interruptor de venta cruzada en salas PÚBLICAS: false = solo la red del
 * creador puede venderla. Default true conserva el comportamiento actual
 * (cualquier red vende las públicas). Incremental para BDs existentes; la
 * definición base está en 2024_01_01_001105_create_bingo_rooms_config_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bingo_rooms_config', 'allow_network_sales')) {
            Schema::table('bingo_rooms_config', function (Blueprint $table) {
                $table->boolean('allow_network_sales')->default(true)->after('min_players')
                    ->comment('PUBLIC: otras redes de venta pueden venderla (venta cruzada)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bingo_rooms_config', 'allow_network_sales')) {
            Schema::table('bingo_rooms_config', fn (Blueprint $table) => $table->dropColumn('allow_network_sales'));
        }
    }
};
