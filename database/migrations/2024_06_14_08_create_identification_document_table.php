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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('value', 6)->comment('Código corto del documento');
            $table->string('description', 50)->comment('Descripción legible del documento');
            $table->foreignId('country_id')->nullable()->constrained('countries')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('is_active')->default(true)
                ->comment('true = activo, false = inactivo');
            $table->timestamps();

            // Índices para optimizar consultas frecuentes
            $table->index('country_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
