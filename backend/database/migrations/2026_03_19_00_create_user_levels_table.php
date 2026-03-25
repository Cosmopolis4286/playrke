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
        Schema::create('user_levels', function (Blueprint $table) {
            $table->snowflake('id')->primary();
            $table->foreignSnowflake('user_id')->constrained('users')->onDelete('cascade')->unique();
            $table->integer('current_level')->default(1);
            $table->integer('current_xp')->default(0);
            $table->integer('total_xp')->default(0);
            $table->integer('xp_to_next_level')->default(1000);
            $table->timestamp('leveled_up_at')->nullable();
            $table->timestamps();

            $table->index(['current_level', 'total_xp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_levels');
    }
};
