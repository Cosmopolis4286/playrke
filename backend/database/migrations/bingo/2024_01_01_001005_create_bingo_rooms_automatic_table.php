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

            $table->enum('in_live', ['Y', 'N'])->default('N')->comment('Indica si produce salas en vivo');

            $table->foreignSnowflake('connection_type_id')
                ->constrained('connection_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Tipo de conexión asignado');

            // Modalidad de participación (Solitario, Múltiple, Ranking, etc.), catálogo
            // per-game reutilizado de game_modes — no confundir con connection_type_id.
            $table->foreignSnowflake('game_mode_id')
                ->nullable()
                ->constrained('game_modes')
                ->nullOnDelete()
                ->comment('Modo de juego asignado (catálogo game_modes, opcional)');

            $table->foreignSnowflake('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete()
                ->comment('Tipo de sala');

            // Configuración de sala privada (solo aplica cuando room_type_id = PRIVATE)
            $table->string('access_code', 20)->nullable()
                ->comment('Código/PIN de acceso para salas privadas');
            $table->string('room_password')->nullable()
                ->comment('Hash (bcrypt) de la contraseña de acceso para salas privadas');
            $table->foreignSnowflake('host_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario anfitrión de la sala privada');

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

            $table->boolean('rooms_per_currency')
                ->default(false)
                ->comment('Si es true, genera una sala independiente por cada moneda permitida');

            $table->foreignSnowflake('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->cascadeOnDelete()
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

            // Mínimo de cartones vendidos para que la sala pueda iniciar su juego.
            // null = sin condición.
            $table->unsignedSmallInteger('min_cards_to_start')
                ->nullable()
                ->comment('Cartones mínimos necesarios para iniciar el juego (null = sin condición)');

            $table->unsignedSmallInteger('max_cards_to_start')
                ->nullable()
                ->comment('Máximo de cartones permitido en la sala (null = sin límite)');

            $table->unsignedSmallInteger('min_players')
                ->nullable()
                ->comment('Cantidad mínima de jugadores requerida para iniciar el sorteo (null = sin condición)');

            $table->unsignedInteger('max_players')
                ->nullable()
                ->comment('Cupo máximo de jugadores en la sala (null = sin límite)');

            // Días de anticipación con que se publica la sala antes de que inicie el juego.
            // null = la sala se publica y juega el mismo día (comportamiento legacy).
            // Ej: 3 → la sala se crea 3 días antes de la hora programada en `time`.
            $table->unsignedTinyInteger('sales_start_offset_days')
                ->nullable()
                ->comment('Días de antelación que se publica la sala antes de que inicie el juego (null = mismo día)');

            $table->boolean('allow_custom_card')->default(false);
            $table->decimal('custom_card_price_pct', 5, 2)->nullable();
            $table->enum('custom_card_mode', ['paid', 'by_range', 'every_x', 'free'])->nullable();
            $table->unsignedSmallInteger('custom_card_every_x')->nullable();

            $table->enum('move_free_space_mode', ['paid', 'by_range', 'free'])->nullable();
            $table->decimal('move_free_space_price_pct', 5, 2)->nullable();

            $table->boolean('allow_special_card')->default(false);
            $table->tinyInteger('max_special_cards')->unsigned()->nullable();

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

            // Configuración por defecto
            $table->foreignSnowflake('default_currency_id')
                ->nullable()
                ->constrained('currencies')
                ->nullOnDelete()
                ->comment('Moneda por defecto para el precio del cartón');

            $table->decimal('default_card_price', 10, 2)
                ->nullable()
                ->comment('Precio por defecto del cartón');

            $table->json('default_config')
                ->nullable()
                ->comment('Configuración por defecto de la sala (JSON consolidado)');

            // Campos consolidados de rondas y premios
            $table->json('rounds_config')
                ->nullable()
                ->comment('Configuración consolidada de rondas (patrones, tipos de premio, valores)');

            $table->json('prize_types')
                ->nullable()
                ->comment('Tipos de premio por ronda');

            $table->json('bingo_patterns')
                ->nullable()
                ->comment('Patrones de bingo por ronda');

            $table->json('prize_values')
                ->nullable()
                ->comment('Valores de premio por ronda');

            // Campos consolidados de precios por moneda
            $table->json('card_currencies')
                ->nullable()
                ->comment('Monedas disponibles para precio del cartón');

            $table->json('card_prices')
                ->nullable()
                ->comment('Precios del cartón por moneda');

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
            $table->index('connection_type_id', 'idx_bingo_rooms_automatic_connection_type_id');
            $table->index('game_mode_id', 'idx_bingo_rooms_automatic_game_mode_id');
            $table->index('host_user_id', 'idx_bingo_rooms_automatic_host_user_id');
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
