<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_types')) {
            return;
        }

        Schema::create('document_types', function (Blueprint $table) {
            $table->snowflake('id')->primary(); // ID único Snowflake
            $table->string('code', 25)->comment('Código corto del documento');
            $table->string('description', 100)->comment('Descripción legible del documento');

            $table->foreignSnowflake('country_id')
                ->nullable()
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();

            // Índice único para evitar duplicados por país
            $table->unique(['country_id', 'code'], 'uq_document_types_country_code');

            // Índice para consultas frecuentes por país y estado
            $table->index(['country_id', 'status'], 'idx_document_types_country_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
