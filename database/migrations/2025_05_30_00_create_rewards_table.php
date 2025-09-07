<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();

            $table->string('code', 12)->comment('Código único del premio');
            $table->enum('reward_type', ['PRODUCT', 'SERVICE'])->comment('Tipo de recompensa');
            $table->string('name', 60);
            $table->enum('kit', ['Y', 'N'])->default('N')->comment('Indica si es un kit');
            $table->tinyInteger('num_items')->default(1)->comment('Número de ítems en el kit');
            $table->decimal('price', 12, 2);
            $table->string('description', 255)->nullable();

            $table->foreignSnowflake('user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();

            // Clave única compuesta: evita duplicados de código por usuario
            $table->unique(['user_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
