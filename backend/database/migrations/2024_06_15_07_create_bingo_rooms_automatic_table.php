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

            $table->string('description', 50)->nullable();
            $table->date('date')->nullable();
            $table->string('days')->nullable();
            $table->string('time', 10);

            $table->foreignSnowflake('game_mode_id')->constrained('game_modes')->onDelete('cascade');

            $table->enum('in_live', ['Y', 'N'])->default('N');
            $table->enum('room_type', ['PRIVATE', 'PUBLIC'])->default('PRIVATE');

            $table->foreignSnowflake('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->onDelete('cascade')
                ->default(1);

            $table->decimal('amount_added_prize_pool', 12, 2)->nullable()
                ->comment('Monto agregado al pool de premios');

            $table->decimal('rke_commission', 8, 2)->default(0)->comment('Comisión RKE');
            $table->decimal('room_commission', 8, 2)->default(0)->comment('Comisión de la sala');

            $table->decimal('card_price', 12, 2)->comment('Precio del cartón');

            $table->json('allowed_currencies')->nullable();
            $table->string('time_chip', 2)->nullable();
            $table->string('rounds', 2)->default('1');
            $table->decimal('rounds_pct', 5, 2)->nullable();
            $table->decimal('serials_pct', 5, 2)->nullable();
            $table->string('num_serials', 2)->nullable();
            $table->decimal('jackpot_pct', 5, 2)->nullable();
            $table->decimal('plays_pct', 5, 2)->nullable();
            $table->json('bingo_patterns')->nullable();

            $table->foreignSnowflake('creator_user_id')->constrained('users')->onDelete('cascade');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();

            // Soft deletes para permitir recuperación de salas eliminadas
            $table->softDeletes();

            // Índices compuestos útiles
            $table->index(['status', 'room_type'], 'idx_status_roomtype_automatic');
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
