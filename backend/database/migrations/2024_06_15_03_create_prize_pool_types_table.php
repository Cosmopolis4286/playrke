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
        Schema::create('prize_pool_types', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID del tipo de premio en el pool');

            $table->string('code', 30)->unique()->comment('Código interno del tipo de premio');
            $table->string('description', 50)->comment('Descripción legible del tipo de premio');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prize_pool_types');
    }
};
