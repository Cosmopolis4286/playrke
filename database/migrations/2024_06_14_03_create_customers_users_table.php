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
        Schema::create('customers_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('user_id'); // Mantengo string, parece ser externo o UUID
            $table->string('brand')->nullable();
            $table->string('token', 255);
            $table->string('country_code', 3)->nullable(); // Mejor nombre para claridad
            $table->string('coin_id', 15);
            $table->timestamp('last_activity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers_users');
    }
};
