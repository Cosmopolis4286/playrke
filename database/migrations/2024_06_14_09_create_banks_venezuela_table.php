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
        Schema::create('banks_venezuela', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('Nombre del banco');
            $table->string('code', 4)->unique()->comment('Código del banco');
            $table->timestamps();

            // Índices
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks_venezuela');
    }
};
