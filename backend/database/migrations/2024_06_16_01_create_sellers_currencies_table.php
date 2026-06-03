<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sellers_currencies')) {
            return;
        }

        Schema::create('sellers_currencies', function (Blueprint $table) {
            // Relaciones
            $table->foreignSnowflake('seller_id')
                ->constrained('sellers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Nivel de asignación
            $table->tinyInteger('level')
                ->default(1)
                ->comment('Nivel asignado: 1 = básico, 2 = intermedio, 3 = avanzado');

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE');

            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Índices para optimizar búsquedas frecuentes
            $table->primary(['seller_id', 'currency_id']);
            $table->index(['seller_id', 'currency_id'], 'idx_seller_currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers_currencies');
    }
};
