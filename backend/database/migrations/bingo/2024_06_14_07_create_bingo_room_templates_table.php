<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_room_templates', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID de la plantilla de sala de bingo');

            $table->string('name', 120)->nullable()->comment('Nombre de la plantilla');
            $table->text('description')->nullable()->comment('Descripción de la plantilla');

            $table->foreignSnowflake('room_type_id')
                ->nullable()
                ->constrained('room_types')
                ->nullOnDelete()
                ->comment('Tipo de sala asociado a la plantilla');

            $table->foreignSnowflake('creator_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario creador de la plantilla');

            $table->foreignSnowflake('game_mode_id')
                ->nullable()
                ->constrained('game_modes')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Modo de juego de la plantilla');

            $table->enum('in_live', ['Y', 'N'])
                ->default('N')
                ->comment('Indica si la plantilla produce salas en vivo');

            $table->boolean('use_base_usd')->default(false)->comment('Usar Base USD; calcular tasa de cambio');
            $table->decimal('card_price_usd', 6, 2)->nullable()->comment('Precio del cartón en USD');
            $table->decimal('usd_exchange_round_step', 4, 2)->nullable()->comment('Incremento de redondeo USD');
            $table->json('allowed_currencies')->nullable()->comment('Monedas permitidas para los precios');

            $table->foreignSnowflake('prize_pool_type_id')
                ->nullable()
                ->constrained('prize_pool_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Tipo de pool de premios de la plantilla');

            $table->decimal('amount_added_prize_pool', 12, 2)->nullable()->comment('Monto adicional agregado al pool de premios');

            $table->string('time_chip', 2)->nullable()->comment('Duración del chip');
            $table->string('rounds', 2)->default('1')->comment('Número de rondas');
            $table->decimal('rounds_pct', 5, 2)->nullable()->comment('Porcentaje aplicado a las rondas');
            $table->boolean('use_serials')->default(false)->comment('Usar seriales');
            $table->decimal('serials_pct', 5, 2)->nullable()->comment('Porcentaje aplicado a seriales');
            $table->string('num_serials', 2)->nullable()->comment('Número de seriales disponibles');
            $table->decimal('jackpot_pct', 5, 2)->nullable()->comment('Porcentaje destinado al jackpot');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')
                ->comment('Estado de la plantilla: ACTIVE = activa, INACTIVE = inactiva');

            $table->timestamps();

            $table->index('room_type_id', 'idx_bingo_room_templates_room_type_id');
            $table->index('creator_user_id', 'idx_bingo_room_templates_creator_user_id');
            $table->index('game_mode_id', 'idx_bingo_room_templates_game_mode_id');
            $table->index('prize_pool_type_id', 'idx_bingo_room_templates_prize_pool_type_id');
            $table->index('status', 'idx_bingo_room_templates_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_room_templates');
    }
};
