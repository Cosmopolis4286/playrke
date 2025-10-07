<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers_games', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;

            $table->foreignSnowflake('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete()
                ->comment('Cliente asociado');

            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnDelete()
                ->comment('Juego asociado');

            $table->string('customer_game_id', 50)
                ->comment('Identificador único del cliente para el juego');

            $table->string('product', 50)
                ->comment('Producto relacionado con el juego');

            $table->string('description', 40)
                ->nullable()
                ->comment('Descripción opcional');

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->index()
                ->comment('Estado del registro');

            $table->timestamps();

            // Índices compuestos para consultas frecuentes
            $table->index(['customer_id', 'game_id']);
            $table->unique(['customer_id', 'customer_game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers_games');
    }
};
