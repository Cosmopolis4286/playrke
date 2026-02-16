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
            $table->string('id', 50)->primary()->comment('ID del tipo de premio');
            $table->string('description', 50)->comment('Descripción legible del tipo de premio');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();

            $table->index('status', 'idx_prize_pool_types_status');
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
