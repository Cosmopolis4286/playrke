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
        Schema::create('bingo_creator_sales', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la venta del creador');
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada');
            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario creador');
            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda asociada');
            $table->decimal('sale_amount', 12, 2)->comment('Monto total de la venta');
            $table->decimal('rke_commission', 6, 2)->comment('Comisión de RKE');
            $table->decimal('room_commission', 6, 2)->comment('Comisión de la sala');
            $table->decimal('seller_commission', 8, 2)->nullable()->comment('Comisión del vendedor');
            $table->decimal('prize_pool_amount', 13, 2)->comment('Monto del pool de premios');
            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['room_id', 'creator_user_id'], 'idx_bingo_creator_sales_room_creator');
            $table->index(['currency_id', 'created_at'], 'idx_bingo_creator_sales_currency_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_creator_sales');
    }
};
