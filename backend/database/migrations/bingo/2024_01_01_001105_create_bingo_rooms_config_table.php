<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('bingo_rooms_config');
    }

    /**
     * Run the migrations.
     */
    public function up() : void
    {
        Schema::create('bingo_rooms_config', function(Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID de la configuración de la sala');

            // Sala asociada
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Sala a la que pertenece la configuración');

            // Plantilla base (opcional)
            $table->foreignSnowflake('template_id')
                ->nullable()
                ->constrained('bingo_room_templates')
                ->nullOnDelete()
                ->comment('Plantilla base usada para esta configuración');

            // Sala automática origen (opcional)
            $table->foreignSnowflake('parent_automatic_id')
                ->nullable()
                ->constrained('bingo_rooms_automatic')
                ->nullOnDelete()
                ->comment('Sala automática que originó esta configuración, si aplica');

            // Origen/estado de la sala
            $table->enum('is_scheduled', ['N', 'Y'])
                ->default('N')
                ->comment('Origen/estado: N=manual, Y=automática');

            $table->enum('from_house', ['Y', 'N'])->default('N')
                ->comment('Sala propia de la app: Y = sí, N = no');

            // Tipo de sala
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

            // Creador
            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario que creó la sala');

            // Tipo de conexión (reemplaza al antiguo game_mode_id)
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

            $table->string('game_variant', 20)
                ->default('BINGO_75')
                ->comment('Variante de bingo: BINGO_75, BINGO_90, BINGO_80, BINGO_30');

            // Estado de transmisión
            $table->enum('in_live', ['Y', 'N'])->default('N')
                ->comment('Indica si la sala está en vivo');

            $table->enum('start_mode', ['SCHEDULED', 'MANUAL'])
                ->default('SCHEDULED')
                ->comment('SCHEDULED = inicia por horario programado, MANUAL = requiere activación manual del anfitrión/admin');

            // Cartones mínimos y cupo máximo
            $table->unsignedInteger('min_cards_to_start')
                ->nullable()
                ->comment('Cantidad mínima de cartones vendidos requerida para iniciar el sorteo (null = sin condición)');

            $table->unsignedInteger('max_players')
                ->nullable()
                ->comment('Cupo máximo de jugadores en la sala (null = sin límite)');

            $table->unsignedInteger('max_cards_to_start')
                ->nullable()
                ->comment('Máximo de cartones permitido en la sala (null = sin límite)');

            $table->unsignedInteger('min_players')
                ->nullable()
                ->comment('Cantidad mínima de jugadores requerida para iniciar el sorteo (null = sin condición)');

            // Sala PÚBLICA: ¿la pueden vender vendedores de OTRAS redes (venta
            // cruzada)? false = solo la red del creador; los jugadores registrados
            // la siguen viendo y comprando igual.
            $table->boolean('allow_network_sales')
                ->default(true)
                ->comment('PUBLIC: otras redes de venta pueden venderla (venta cruzada)');

            // Sala VIP: acceso automático (sin invitación) para jugadores registrados
            // que cumplan TODOS los requisitos fijados (ver BingoRoomInvitationService).
            $table->unsignedSmallInteger('vip_min_level')
                ->nullable()
                ->comment('VIP: nivel mínimo del jugador para entrar sin invitación (null = sin requisito)');

            $table->decimal('vip_min_spend_usd', 12, 2)
                ->nullable()
                ->comment('VIP: gasto mínimo en USD de los últimos 30 días para entrar sin invitación (null = sin requisito)');

            // Cartón personalizado
            $table->boolean('allow_custom_card')->default(false);

            // % adicional sobre el precio base del cartón en la moneda de la sala,
            // aplicado al momento de la compra del cartón personalizado.
            $table->decimal('custom_card_price_pct', 5, 2)->nullable();

            // Modo del cartón personalizado (solo aplica si allow_custom_card = true):
            // - paid: costo adicional (custom_card_price_pct)
            // - by_range: recargo % calculado automáticamente según el precio base del cartón
            // - every_x: gratis cada X cartones comprados (custom_card_every_x)
            // - free: uso libre, sin restricción ni costo adicional
            $table->enum('custom_card_mode', ['paid', 'by_range', 'every_x', 'free'])
                ->nullable();
            $table->unsignedSmallInteger('custom_card_every_x')->nullable();

            // Mover la casilla libre (solo aplica si allow_custom_card = true y
            // game_variant = BINGO_75, única variante con casilla libre):
            // - paid: recargo % manual (move_free_space_price_pct)
            // - by_range: recargo % calculado automáticamente según el precio del cartón
            // - free: mover la libre sin costo adicional
            // - null: no permite mover la libre (comportamiento por defecto)
            $table->enum('move_free_space_mode', ['paid', 'by_range', 'free'])
                ->nullable();
            $table->decimal('move_free_space_price_pct', 5, 2)->nullable();

            // Cartón especial: independiente de allow_custom_card — solo limita la
            // cantidad máxima de cartones especiales que puede tener la sala.
            $table->boolean('allow_special_card')->default(false);
            $table->tinyInteger('max_special_cards')->unsigned()->nullable();

            $table->date('date')->nullable()->comment('Fecha del juego (null si la sala es de inicio manual)');
            $table->string('time', 10)->nullable()->comment('Hora del juego (null si la sala es de inicio manual)');
            $table->unsignedInteger('unix_date')->nullable()->comment('Fecha en formato timestamp UNIX (null si la sala es de inicio manual)');

            // Comisiones
            $table->decimal('rke_commission', 8, 2)->nullable()->comment('Comisión del RKE');
            $table->decimal('room_commission', 8, 2)->nullable()->comment('Comisión de la sala');

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

            $table->boolean('rooms_per_currency_split')
                ->default(false)
                ->comment('Indica si esta sala fue generada como parte de un split por moneda');

            // Tipo de premio
            $table->foreignSnowflake('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Tipo de pool de premio');

            $table->decimal('amount_added_prize_pool', 12, 2)->nullable()
                ->comment('Monto adicional agregado al pool de premios');

            // Configuración de tiempo y rondas
            $table->string('time_chip', 2)->nullable()->comment('Duración del chip');

            $table->string('rounds', 2)->default('1')->comment('Número de rondas');
            $table->decimal('rounds_pct', 5, 2)->nullable()->comment('Porcentaje aplicado a las rondas');

            $table->boolean('use_serials')->default(false);
            $table->decimal('serials_pct', 5, 2)->nullable()->comment('Porcentaje aplicado a seriales');
            $table->string('num_serials', 2)->nullable()->comment('Número de seriales disponibles');

            $table->decimal('jackpot_pct', 5, 2)->nullable()->comment('Porcentaje destinado al jackpot');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')
                ->comment('Estado de la configuración: ACTIVE = activa, INACTIVE = inactiva');

            $table->timestamps(); // created_at y updated_at

            // indices
            $table->index('room_id', 'idx_room_id');
            $table->index('template_id', 'idx_bingo_rooms_config_template_id');
            $table->index('parent_automatic_id', 'idx_bingo_rooms_config_parent_automatic_id');
            $table->index('is_scheduled', 'idx_bingo_rooms_config_is_scheduled');
            $table->index('room_type_id', 'idx_room_type_id');
            $table->index('creator_user_id', 'idx_creator_user_id');
            $table->index('connection_type_id', 'idx_connection_type_id');
            $table->index('game_mode_id', 'idx_bingo_rooms_config_game_mode_id');
            $table->index('host_user_id', 'idx_bingo_rooms_config_host_user_id');
            $table->index('prize_pool_type_id', 'idx_prize_pool_type_id');
            $table->index(['date', 'time'], 'idx_datetime');
            $table->index(['status'], 'idx_status');
            $table->index('start_mode', 'idx_bingo_rooms_config_start_mode');
            $table->index('unix_date', 'idx_bingo_rooms_config_unix_date');
            $table->index(['from_house', 'date'], 'idx_bingo_rooms_config_house_date');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel
        });
    }
};
