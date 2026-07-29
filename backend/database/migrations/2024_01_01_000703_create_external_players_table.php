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
            $table->string('link', 28)->unique()->comment('Link único del jugador externo');

            // Fecha de expiración
            $table->timestamp('expiration_date')->nullable()->comment('Fecha de expiración del código');

            // Relaciones principales
            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del juego asociado');

            $table->string('room_id')->comment('ID de la sala');

            $table->string('room_user_id')->comment('ID de usuario dentro de la sala');

            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario creador de la sala de juego');

            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID del país del jugador');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID de la moneda utilizada');

            $table->foreignSnowflake('seller_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->comment('ID del usuario vendedor asociado');

            $table->foreignSnowflake('purchase_code_id')
                ->constrained('user_purchase_codes')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Id del código de compra asociado');

            // Datos del jugador
            $table->string('first_name', 50)->comment('Nombre del jugador');
            $table->string('last_name', 50)->comment('Apellido del jugador');
            $table->string('phone', 20)->comment('Teléfono del jugador');
            $table->string('identification', 30)->nullable()->comment('Documento de identificación');

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado del jugador');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Índices compuestos para consultas frecuentes
            $table->index(['game_id', 'room_id', 'creator_user_id'], 'idx_game_room_creator');
            $table->index(['country_id', 'currency_id'], 'idx_country_currency');
            $table->index(['seller_user_id', 'purchase_code_id'], 'idx_seller_purchase');
            $table->index('status', 'idx_status');
            $table->index('link', 'idx_link');
            $table->index('phone', 'idx_external_players_phone');
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
