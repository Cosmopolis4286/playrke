<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers_exchange_rates', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;

            $table->foreignSnowflake('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete()
                ->comment('Cliente asociado');

            $table->string('currency_id', 20)->comment('Moneda asociada');
            $table->decimal('amount', 12, 4)
                ->default(1)
                ->comment('Valor de cambio de la moneda');

            $table->foreignSnowflake('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que registró/modificó');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])
                ->default('ACTIVE')
                ->index()
                ->comment('Estado del tipo de cambio');

            $table->decimal('percentage', 6, 3)->default(0)->comment('Porcentaje aplicado');

            $table->timestamps();

            // Índices compuestos para consultas frecuentes
            $table->index(['customer_id', 'currency_id']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers_exchange_rates');
    }
};
