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
            $table->string('code', 15)->unique();
            $table->enum('reward_type', ['P', 'S']);
            $table->string('name', 50);
            $table->enum('kit', ['Y', 'N'])->default('N');
            $table->string('num_items', 2)->default('1');
            $table->double('price');
            $table->text('description');
            $table->foreignId('user')->constrained('users')->onDelete('cascade');
            $table->boolean('status')->default(true);
            $table->timestamps();
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
