<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_numbers', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario propietario del número');

            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnDelete()
                ->comment('País del número');

            $table->string('phone_number', 20)
                ->comment('Número de teléfono incluyendo código país');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index(['user_id', 'status']);
            $table->index(['country_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
    }
};
