<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerces_countries_coins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('commerce_id')
                ->constrained('commerces')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('country_id')
                ->nullable()
                ->constrained('countries')
                ->cascadeOnUpdate();

            $table->foreignId('coin_id')
                ->constrained('coins')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->tinyInteger('level')
                ->comment('Nivel asignado: 1 = básico, 2 = intermedio, 3 = avanzado');

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active')
                ->comment('Estado activo/inactivo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerces_countries_coins');
    }
};
