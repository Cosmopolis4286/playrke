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
        Schema::create('bingo_rooms_automatic', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la sala automática');

            $table->json('days')->nullable()->comment('Días de la semana para generación');
            $table->string('time', 10)->comment('Hora programada');
            $table->enum('in_live', ['Y', 'N'])->default('N')->comment('Indica si produce salas en vivo');

            $table->foreignSnowflake('game_mode_id')
                ->constrained('game_modes')
                ->cascadeOnDelete()
                ->comment('Modo de juego');

            $table->foreignSnowflake('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete()
                ->comment('Tipo de sala');

            $table->foreignSnowflake('template_id')
                ->nullable()
                ->constrained('bingo_room_templates')
                ->nullOnDelete()
                ->comment('Plantilla base usada por esta sala automática');

            // Comisiones
            $table->decimal('rke_commission', 8, 2)->default(0)->comment('Comisión RKE');
            $table->decimal('room_commission', 8, 2)->default(0)->comment('Comisión de la sala');

            // Usar base USD
            $table->boolean('use_base_usd')->default(false)->comment('Usar Base USD; calcular tasa de cambio');

            // Precio Carton en USD
            $table->decimal('card_price_usd', 6, 2)->nullable()->comment('Precio del Cartón en USD');

            // Incremento de redondeo
            $table->decimal('usd_exchange_round_step', 4, 2)
                ->nullable()
                ->comment('Incremento de redondeo USD (null = sin redonde0, 1.00 = entero, 0.50 = múltiplos de 0.5)');

            // Monedas permitidas para el precio del cartón
            $table->json('allowed_currencies')->nullable()
                ->comment('Monedas permitidas para el precio del cartón');

            $table->foreignSnowflake('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->onDelete('cascade')
                ->default(1)
                ->comment('Tipo de pool de premios');

            $table->decimal('amount_added_prize_pool', 10, 2)->nullable()
                ->comment('Monto agregado al pool de premios');

            $table->string('time_chip', 2)->nullable()->comment('Duración del chip');

            $table->string('rounds', 2)->default('1')->comment('Número de rondas');
            $table->decimal('rounds_pct', 5, 2)->nullable()->comment('Porcentaje aplicado a rondas');

            $table->boolean('use_serials')->default(false)->comment('Usar seriales');
            $table->decimal('serials_pct', 5, 2)->nullable()->comment('Porcentaje aplicado a seriales');
            $table->string('num_serials', 2)->nullable()->comment('Número de seriales');

            $table->decimal('jackpot_pct', 5, 2)->nullable();

            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario creador');

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'SUSPENDED'])
                ->default('INACTIVE')
                ->comment('Estado de la sala automática');

            // Campos adicionales para programación avanzada (desde schedules)
            $table->enum('recurrence_type', ['DAILY', 'WEEKLY', 'MONTHLY', 'CUSTOM'])
                ->default('WEEKLY')
                ->comment('Tipo de recurrencia del horario');

            $table->json('recurrence_days')
                ->nullable()
                ->comment('Días de recurrencia: [1,2,3,4,5] para lunes-viernes, [6,0] para sábado-domingo');

            $table->time('game_time')
                ->nullable()
                ->comment('Hora del juego programado');

            // Fechas de vigencia
            $table->date('start_date')
                ->nullable()
                ->comment('Fecha de inicio de la programación');

            $table->date('end_date')
                ->nullable()
                ->comment('Fecha de fin de la programación (null = sin fin)');

            // Control avanzado de creación
            $table->boolean('auto_create_room')
                ->default(true)
                ->comment('Crear sala automáticamente');

            $table->integer('max_rooms_per_day')
                ->nullable()
                ->comment('Límite de salas por día (null = sin límite)');

            // Contadores
            $table->integer('rooms_created')->default(0)->comment('Total de salas creadas');
            $table->timestamp('last_room_created_at')
                ->nullable()
                ->comment('Fecha y hora de la última sala creada');

            $table->timestamps();

            // Soft deletes para permitir recuperación de salas eliminadas
            $table->softDeletes();

            // Índices compuestos útiles
            $table->index(['status', 'room_type_id'], 'idx_status_roomtype_automatic');
            // NOTA: índice en created_at eliminado - gestionado por Laravel
            $table->index(['creator_user_id', 'created_at'], 'idx_creator_createdat_automatic');

            // Índices para nuevos campos de programación avanzada
            $table->index(['recurrence_type', 'status'], 'idx_bingo_rooms_automatic_type_status');
            $table->index(['game_time', 'status'], 'idx_bingo_rooms_automatic_time_status');
            $table->index(['start_date', 'end_date'], 'idx_bingo_rooms_automatic_dates');
            $table->index(['status', 'in_live'], 'idx_bingo_rooms_automatic_status_live');
            $table->index('prize_pool_type_id', 'idx_bingo_rooms_automatic_prize_pool');
            $table->index(['auto_create_room', 'status'], 'idx_bingo_rooms_automatic_auto_status');
            $table->index('last_room_created_at', 'idx_bingo_rooms_automatic_last_created');
            $table->index('template_id', 'idx_bingo_rooms_automatic_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rooms_automatic');
    }
};
