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
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Sala asociada');
            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Usuario creador');
            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Moneda asociada');
            // Cada fila ACUMULA todas las ventas de una sala por creador y moneda
            // (moneda local: VES, etc.), así que todas las columnas de montos van
            // con el mismo ancho — antes rke/room eran decimal(6,2) (tope 9.999,99)
            // y desbordaban con las ventas acumuladas de una sola sala.
            $table->decimal('sale_amount', 13, 2)->comment('Monto total de la venta');
            $table->decimal('rke_commission', 13, 2)->comment('Comisión de RKE');
            $table->decimal('room_commission', 13, 2)->comment('Comisión de la sala');
            $table->decimal('seller_commission', 13, 2)->nullable()->comment('Comisión del vendedor');
            $table->decimal('subsidiary_commission', 13, 2)->default(0)->comment('Comisión de agencias (override sobre el vendedor)');
            $table->decimal('commerce_commission', 13, 2)->default(0)->comment('Comisión de comercializadoras (override de red)');
            $table->decimal('jackpot_contribution', 13, 2)->default(0)->comment('Aporte al jackpot');
            $table->decimal('prize_pool_amount', 13, 2)->comment('Monto destinado a premios: bote de la sala, o fondos del premio físico en salas Premio/Servicio puras');
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
