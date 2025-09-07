<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasurers', function (Blueprint $table) {
            $table->id();

            $table->string('name', 75)->comment('Nombre del tesorero');

            $table->foreignId('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID de moneda asociada');

            $table->string('phone', 20)->nullable()->comment('Teléfono de contacto');

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->uuid('uuid_code')->unique()->comment('Identificador único universal');

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->index();

            $table->timestamps();

            // Índice compuesto si consultas frecuentes por país y estado
            $table->index(['country_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasurers');
    }
};
