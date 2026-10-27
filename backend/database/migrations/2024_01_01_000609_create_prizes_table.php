<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('prizes');
    }

    public function up() : void
    {
        Schema::create('prizes', function(Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();

            $table->string('code', 12)->comment('Código único del premio');
            $table->string('slug', 80);
            $table->enum('reward_type', ['PRODUCT', 'SERVICE', 'MIXED'])->comment('Tipo de recompensa');
            $table->string('name', 60);
            $table->decimal('price', 12, 2);
            $table->string('description', 255)->nullable();
            $table->enum('kit', ['Y', 'N'])->default('N')->comment('Indica si es un kit');
            $table->tinyInteger('num_items')->default(1)->comment('Número de ítems en el kit');
            $table->json('kit_items')->nullable()->comment('IDs de prizes individuales que componen el kit');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('ID del usuario que creó el premio');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();

            $table->softDeletes();

            // Clave única compuesta: evita duplicados de código por usuario
            $table->unique(['user_id', 'code']);
        });
    }
};
