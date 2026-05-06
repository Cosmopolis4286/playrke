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
        Schema::create('bingo_room_schedules', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del horario programado');

            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada al horario');

            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario que programó el horario');

            // Configuración de recurrencia
            $table->enum('recurrence_type', ['DAILY', 'WEEKLY', 'MONTHLY', 'CUSTOM'])
                ->default('WEEKLY')
                ->comment('Tipo de recurrencia del horario');

            $table->json('recurrence_days')
                ->nullable()
                ->comment('Días de recurrencia: [1,2,3,4,5] para lunes-viernes, [6,0] para sábado-domingo');

            $table->time('game_time')
                ->comment('Hora del juego programado');

            // Fechas de vigencia
            $table->date('start_date')
                ->comment('Fecha de inicio de la programación');

            $table->date('end_date')
                ->nullable()
                ->comment('Fecha de fin de la programación (null = sin fin)');

            // Configuración automática de sala
            $table->json('default_config')
                ->nullable()
                ->comment('Configuración por defecto para salas automáticas');

            $table->decimal('default_card_price', 12, 2)
                ->nullable()
                ->comment('Precio por defecto del cartón');

            $table->foreignSnowflake('default_currency_id')
                ->nullable()
                ->constrained('currencies')
                ->nullOnDelete()
                ->comment('Moneda por defecto');

            // Estado y control
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'SUSPENDED'])
                ->default('ACTIVE')
                ->comment('Estado del horario programado');

            $table->boolean('auto_create_room')
                ->default(true)
                ->comment('Crear sala automáticamente');

            // Campos adicionales desde bingo_rooms_automatic para fusión completa
            $table->enum('in_live', ['Y', 'N'])->default('N')
                ->comment('Indica si la sala está en vivo');

            $table->json('allowed_currencies')->nullable()
                ->comment('Monedas permitidas para el precio del cartón');

            $table->foreignSnowflake('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->onDelete('cascade')
                ->default(1)
                ->comment('Tipo de pool de premio');

            $table->decimal('amount_added_prize_pool', 12, 2)->nullable()
                ->comment('Monto adicional agregado al pool de premios');

            $table->string('time_chip', 2)->nullable()
                ->comment('Duración del chip');

            $table->string('rounds', 2)->default('1')
                ->comment('Número de rondas');

            $table->decimal('rounds_pct', 5, 2)->nullable()
                ->comment('Porcentaje aplicado a las rondas');

            $table->boolean('use_serials')->default(false)
                ->comment('Usar seriales');

            $table->decimal('serials_pct', 5, 2)->nullable()
                ->comment('Porcentaje aplicado a seriales');

            $table->string('num_serials', 2)->nullable()
                ->comment('Número de seriales disponibles');

            $table->decimal('jackpot_pct', 5, 2)->nullable()
                ->comment('Porcentaje destinado al jackpot');

            $table->integer('max_rooms_per_day')
                ->nullable()
                ->comment('Límite de salas por día (null = sin límite)');

            // Contadores
            $table->integer('rooms_created')->default(0)->comment('Total de salas creadas');
            $table->timestamp('last_room_created_at')
                ->nullable()
                ->comment('Fecha y hora de la última sala creada');

            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frecuentes
            $table->index('room_id', 'idx_bingo_room_schedules_room');
            $table->index('creator_user_id', 'idx_bingo_room_schedules_creator');
            $table->index(['recurrence_type', 'status'], 'idx_bingo_room_schedules_type_status');
            $table->index(['game_time', 'status'], 'idx_bingo_room_schedules_time_status');
            $table->index(['start_date', 'end_date'], 'idx_bingo_room_schedules_dates');
            $table->index(['status', 'in_live'], 'idx_bingo_room_schedules_status_live');
            $table->index('prize_pool_type_id', 'idx_bingo_room_schedules_prize_pool');
            $table->index(['auto_create_room', 'status'], 'idx_bingo_room_schedules_auto_status');
            $table->index('created_at', 'idx_bingo_room_schedules_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_room_schedules');
    }
};
