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
            $table->foreignId('room_id')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->string('coin', 15);
            $table->decimal('sale_amount', 12, 2);
            $table->decimal('rke_commn', 6, 2);
            $table->decimal('room_commn', 6, 2);
            $table->decimal('seller_commn', 8, 2)->nullable();
            $table->decimal('prize_pool_amount', 13, 2);
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
