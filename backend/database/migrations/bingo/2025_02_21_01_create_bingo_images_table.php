<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_images', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la imagen');

            $table->foreignSnowflake('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que subió la imagen');

            $table->enum('type', ['TYPE1', 'TYPE2'])
                ->comment('Tipo de imagen');

            $table->string('param', 15)->comment('Parámetro asociado a la imagen');
            $table->string('route', 255)->comment('Ruta del archivo de la imagen');
            $table->string('code', 8)->comment('Código identificador de la imagen');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])
                ->default('ACTIVE');

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['user_id', 'type', 'status'], 'idx_bingo_images_lookup');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_images');
    }
};
