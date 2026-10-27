<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('bingo_patterns');
    }

    /**
     * Run the migrations.
     */
    public function up() : void
    {
        Schema::create('bingo_patterns', function(Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del patrón');
            $table->enum('default_pattern', ['Y', 'N'])->default('N')->comment('Patrón por defecto del sistema');
            // num_id: identificador legible y estable.
            // Patrones de sistema (default_pattern='Y'): user_id=NULL, num_id único global (1-30 reservados).
            // Patrones de usuario: num_id único por user_id, empieza en 31 (evita colisiones entre usuarios).
            $table->unsignedTinyInteger('num_id');
            $table->enum('type_pattern', ['UNIQUE', 'MULTIPLE']);
            $table->string('name', 30);
            $table->foreignSnowflake('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario propietario del patrón');
            $table->string('boxes', 150);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();

            $table->unique(['num_id', 'user_id']);
            $table->index('user_id', 'idx_bingo_patterns_user');
            $table->index('status', 'idx_bingo_patterns_status');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel
            $table->index(['user_id', 'num_id'], 'idx_bingo_patterns_user_numid');
        });
    }
};
