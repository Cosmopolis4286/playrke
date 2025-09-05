<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers_exchange_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete()
                ->comment('Cliente asociado');

            $table->string('coin_id', 20)->comment('Moneda asociada');
            $table->decimal('exchange_rate', 15, 6)->default(1)->comment('Tipo de cambio');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que registró/modificó');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])
                ->default('ACTIVE')
                ->index()
                ->comment('Estado del tipo de cambio');

            $table->decimal('pct', 6, 3)->default(0)->comment('Porcentaje aplicado');

            $table->timestamps();

            // Índices compuestos para consultas frecuentes
            $table->index(['customer_id', 'coin']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers_exchange_rates');
    }
};
