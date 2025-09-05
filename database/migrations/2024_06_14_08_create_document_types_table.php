<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();

            $table->string('code', 10)
                ->unique()
                ->comment('Código corto del documento');

            $table->string('description', 100)
                ->comment('Descripción legible del documento');

            $table->foreignId('country_id')
                ->nullable()
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->boolean('is_active')
                ->default(true)
                ->comment('true = activo, false = inactivo');

            $table->timestamps();

            // Índice compuesto opcional si consultas mucho por país + estado
            $table->index(['country_id', 'is_active'], 'country_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
