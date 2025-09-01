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
        Schema::create('bingo_creator_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->string('coin', 15);
            $table->double('sale_amount');
            $table->double('rke_commn');
            $table->double('room_commn');
            $table->double('seller_commn')->nullable();
            $table->double('prize_pool_amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_creator_sales');
    }
};
