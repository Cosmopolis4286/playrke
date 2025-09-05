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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coin_id')->constrained('coins')
                ->cascadeOnUpdate()->cascadeOnDelete()
                ->comment('ID de la moneda asociada');
            $table->decimal('value', 12, 4)->default(1);
            $table->timestamps();

            // Índice compuesto por si consultas coin + value frecuentemente
            $table->index(['coin_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
