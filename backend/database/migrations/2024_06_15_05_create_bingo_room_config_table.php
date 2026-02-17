<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bingo_rooms_config', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID de la configuración de la sala');

            // Sala asociada
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Sala a la que pertenece la configuración');

            $table->enum('creation', ['M', 'A'])->default('M')
                ->comment('Modo de creación: M = manual, A = automático');

            // Tipo de sala
            $table->foreignSnowflake('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete()
                ->comment('Tipo de sala');

            // Creador
            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario que creó la sala');

            // Modo de juego
            $table->foreignSnowflake('game_mode_id')
                ->constrained('game_modes')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Modo de juego asignado a la sala');

            // Estado de transmisión
            $table->enum('in_live', ['Y', 'N'])->default('N')
                ->comment('Indica si la sala está en vivo');

            $table->date('date')->comment('Fecha del juego');
            $table->string('time', 10)->comment('Hora del juego');
            $table->integer('unix_date')->comment('Fecha en formato timestamp UNIX');

            // Comisiones
            $table->decimal('rke_commission', 8, 2)->nullable()->comment('Comisión del RKE');
            $table->decimal('room_commission', 8, 2)->nullable()->comment('Comisión de la sala');

            // Monedas permitidas para el precio del cartón
            $table->json('allowed_currencies')->nullable()
                ->comment('Monedas permitidas para el precio del cartón');

            // Tipo de premio
            $table->foreignSnowflake('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->default(1)
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Tipo de pool de premio');

            $table->decimal('amount_added_prize_pool', 12, 2)->nullable()
                ->comment('Monto adicional agregado al pool de premios');

            // Configuración de tiempo y rondas
            $table->string('time_chip', 2)->nullable()->comment('Duración del chip');
            $table->string('rounds', 2)->default('1')->comment('Número de rondas');
            $table->decimal('rounds_pct', 5, 2)->nullable()->comment('Porcentaje aplicado a las rondas');
            $table->decimal('serials_pct', 5, 2)->nullable()->comment('Porcentaje aplicado a seriales');
            $table->tinyInteger('num_serials')->nullable()->comment('Número de seriales disponibles');
            $table->decimal('jackpot_pct', 5, 2)->nullable()->comment('Porcentaje destinado al jackpot');
            $table->decimal('plays_pct', 5, 2)->nullable()->comment('Porcentaje destinado a las jugadas');

            $table->timestamps(); // created_at y updated_at

            // indices
            $table->index('room_id', 'idx_room_id');
            $table->index('room_type_id', 'idx_room_type_id');
            $table->index('creator_user_id', 'idx_creator_user_id');
            $table->index('game_mode_id', 'idx_game_mode_id');
            $table->index('prize_pool_type_id', 'idx_prize_pool_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rooms_config');
    }
};
