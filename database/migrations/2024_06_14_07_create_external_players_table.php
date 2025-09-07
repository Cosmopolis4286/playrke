<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'external_players'.
     */
    public function up(): void
    {
        Schema::create('external_players', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del jugador externo');

            // Link único
            $table->string('link', 25)->unique()->comment('Link único del jugador externo');

            // Relaciones principales
            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Juego asociado');

            $table->unsignedBigInteger('room')->comment('ID de la sala');

            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario creador');

            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('País del jugador');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Moneda utilizada');

            $table->foreignSnowflake('seller_id')
                ->nullable()
                ->constrained('sellers')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Vendedor asociado');

            $table->foreignSnowflake('purchase_code_id')
                ->constrained('user_purchase_codes')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Código de compra asociado');

            // Datos del jugador
            $table->unsignedBigInteger('room_user')->comment('Número de usuario dentro de la sala');
            $table->string('first_name', 50)->comment('Nombre del jugador');
            $table->string('last_name', 50)->comment('Apellido del jugador');
            $table->string('identification', 20)->nullable()->comment('Documento de identificación');
            $table->string('phone', 14)->comment('Teléfono del jugador');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado del jugador');

            // Timestamps
            $table->timestamps();

            // Índices compuestos para consultas frecuentes
            $table->index(['game_id', 'room', 'creator_user_id'], 'idx_game_room_creator');
            $table->index(['country_id', 'currency_id'], 'idx_country_currency');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'external_players'.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_players');
    }
};
