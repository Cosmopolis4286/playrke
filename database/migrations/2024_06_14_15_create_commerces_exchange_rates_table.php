<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerces_exchange_rates', function (Blueprint $table) {
            $table->id();

            // Comercio relacionado
            $table->foreignId('commerce_id')
                ->constrained('commerces')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Moneda relacionada
            $table->foreignId('coin_id')->constrained('coins')->cascadeOnUpdate();

            $table->double('exchange_rate', 10, 4)->default(1)->comment('Valor de cambio de la moneda');
            $table->enum('status', ['active', 'inactive', 'old', 'directory'])->default('active');
            $table->string('pct', 8)->default('0')->comment('Porcentaje asociado a la tasa de cambio');

            $table->timestamps();

            // Índices
            $table->unique(['commerce_id', 'coin_id'], 'commerce_coin_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerces_exchange_rates');
    }
};
