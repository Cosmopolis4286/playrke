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
        Schema::create('user_purchase_codes', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('last_name', 50)->nullable();
            $table->foreignId('country_id')->constrained('countries');
            $table->string('phone', 14);
            $table->string('code', 12)->unique();
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->timestamp('expiration_date')->nullable();
            $table->timestamps();

            // Índice compuesto opcional para búsquedas rápidas
            $table->index(['country_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_purchase_codes');
    }
};
