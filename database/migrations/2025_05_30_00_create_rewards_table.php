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
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12);
            $table->enum('reward_type', ['product', 'service']);
            $table->string('name', 50);
            $table->enum('kit', ['Y', 'N'])->default('N');
            $table->string('num_items', 2)->default('1');
            $table->decimal('price', 12, 2);
            $table->text('description');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Clave única compuesta
            $table->unique(['user', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
