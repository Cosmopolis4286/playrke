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
            $table->snowflake('id')->primary();;

            $table->date('date')->nullable();
            $table->string('days')->nullable();
            $table->string('time', 10);
            $table->enum('in_live', ['Y', 'N'])->default('N');

            $table->foreignSnowflake('game_mode_id')->constrained('game_modes')->onDelete('cascade');

            $table->foreignSnowflake('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete()
                ->comment('Tipo de sala');

            // Comisiones
            $table->decimal('rke_commission', 8, 2)->default(0)->comment('Comisión RKE');
            $table->decimal('room_commission', 8, 2)->default(0)->comment('Comisión de la sala');

            // Precio Carton en USD
            $table->decimal('card_price_usd', 6, 2)->nullable()->comment('Precio del Cartón en USD');

            // Monedas permitidas para el precio del cartón
            $table->json('allowed_currencies')->nullable()
                ->comment('Monedas permitidas para el precio del cartón');

            $table->foreignSnowflake('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->onDelete('cascade')
                ->default(1);

            $table->decimal('amount_added_prize_pool', 10, 2)->nullable()
                ->comment('Monto agregado al pool de premios');

            $table->string('time_chip', 2)->nullable();

            $table->tinyInteger('rounds', 2)->default(1);
            $table->decimal('rounds_pct', 5, 2)->nullable();

            $table->enum('use_serials', ['Y', 'N'])->default('N');
            $table->decimal('serials_pct', 5, 2)->nullable();
            $table->tinyInteger('num_serials', 2)->nullable();

            $table->decimal('jackpot_pct', 5, 2)->nullable();

            $table->foreignSnowflake('creator_user_id')->constrained('users')->onDelete('cascade');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');

            $table->timestamps();

            // Soft deletes para permitir recuperación de salas eliminadas
            $table->softDeletes();

            // Índices compuestos útiles
            $table->index(['status', 'room_type_id'], 'idx_status_roomtype_automatic');
            $table->index(['creator_user_id', 'created_at'], 'idx_creator_createdat_automatic');
            $table->index(['date'], 'idx_date_automatic');
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
