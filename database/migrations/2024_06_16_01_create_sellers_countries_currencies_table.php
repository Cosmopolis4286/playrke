<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers_countries_currencies', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignSnowflake('seller_id')
                ->constrained('sellers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignSnowflake('country_id')
                ->nullable()                // IMPORTANTE: nullable para SET NULL
                ->constrained('countries')
                ->nullOnDelete()            // ON DELETE SET NULL
                ->cascadeOnUpdate();

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->tinyInteger('level')
                ->comment('1 = básico, 2 = medio, 3 = avanzado');

            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE');

            $table->timestamps();

            // Evitar duplicados por combinación
            $table->unique(['seller_id', 'country_id', 'currency_id'], 'unique_seller_country_currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers_countries_currencies');
    }
};
