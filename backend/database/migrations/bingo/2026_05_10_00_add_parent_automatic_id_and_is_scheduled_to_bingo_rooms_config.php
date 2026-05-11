<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            // Agregar parent_automatic_id para rastrear qué plantilla automática originó esta configuración
            $table->foreignSnowflake('parent_automatic_id')
                ->nullable()
                ->after('room_id')
                ->constrained('bingo_rooms_automatic')
                ->nullOnDelete()
                ->comment('Sala automática que originó esta configuración, si aplica');

            // Agregar is_scheduled para marcar el origen/estado de la sala
            $table->enum('is_scheduled', ['N', 'Y', 'A'])
                ->default('N')
                ->after('parent_automatic_id')
                ->comment('Origen/estado de la sala: N=manual, Y=programada, A=automática');

            $table->index('parent_automatic_id', 'idx_bingo_rooms_config_parent_automatic_id');
            $table->index('is_scheduled', 'idx_bingo_rooms_config_is_scheduled');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropForeign(['parent_automatic_id']);
            $table->dropIndex('idx_bingo_rooms_config_parent_automatic_id');
            $table->dropIndex('idx_bingo_rooms_config_is_scheduled');
            $table->dropColumn(['parent_automatic_id', 'is_scheduled']);
        });
    }
};
