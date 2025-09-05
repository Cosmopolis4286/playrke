<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario propietario del número');

            $table->foreignId('country_id')
                ->constrained('countries')
                ->cascadeOnDelete()
                ->comment('País del número');

            $table->string('phone_number', 20)
                ->comment('Número de teléfono incluyendo código país');

            $table->boolean('is_active')
                ->default(true)
                ->comment('true = activo, false = inactivo');

            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index(['user_id', 'is_active']);
            $table->index(['country_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
    }
};
