<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Requisitos opcionales de acceso automático a salas VIP: un jugador
 * registrado que cumpla todos los fijados entra sin estar en la lista de
 * invitados. Incremental para BDs existentes; la definición base está en
 * 2024_01_01_001105_create_bingo_rooms_config_table.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::table('bingo_rooms_config', function(Blueprint $table) {
            foreach (['vip_min_spend_usd', 'vip_min_level'] as $column) {
                if (Schema::hasColumn('bingo_rooms_config', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function up() : void
    {
        Schema::table('bingo_rooms_config', function(Blueprint $table) {
            if (! Schema::hasColumn('bingo_rooms_config', 'vip_min_level')) {
                $table->unsignedSmallInteger('vip_min_level')->nullable()->after('min_players')
                    ->comment('VIP: nivel mínimo del jugador para entrar sin invitación (null = sin requisito)');
            }
            if (! Schema::hasColumn('bingo_rooms_config', 'vip_min_spend_usd')) {
                $table->decimal('vip_min_spend_usd', 12, 2)->nullable()->after('vip_min_level')
                    ->comment('VIP: gasto mínimo en USD de los últimos 30 días para entrar sin invitación (null = sin requisito)');
            }
        });
    }
};
