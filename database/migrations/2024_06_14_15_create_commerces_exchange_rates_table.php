<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'commerces_exchange_rates'.
     */
    public function up(): void
    {
        Schema::create('commerces_exchange_rates', function (Blueprint $table) {
            $table->id()->comment('ID único del registro de tasa de cambio');

            // Comercio relacionado
            $table->foreignId('commerce_id')
                ->constrained('commerces')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Comercio al que aplica la tasa');

            // Moneda relacionada
            $table->foreignId('coin_id')
                ->constrained('coins')
                ->cascadeOnUpdate()
                ->comment('Moneda asociada a la tasa');

            // Datos de la tasa
            $table->decimal('exchange_rate', 12, 4)
                ->default(1)
                ->comment('Valor de cambio de la moneda');
            $table->double('percentage', 8, 2)
                ->default(0)
                ->comment('Porcentaje adicional asociado a la tasa');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'OLD', 'DIRECTORY'])
                ->default('ACTIVE')
                ->comment('Estado de la tasa de cambio');

            $table->timestamps();

            // Restricción única por comercio y moneda
            $table->unique(['commerce_id', 'coin_id'], 'commerce_coin_unique');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'commerces_exchange_rates'.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerces_exchange_rates');
    }
};
