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
        Schema::create('bingo_ctn_prices', function (Blueprint $table) {
            $table->id();
            // Claves foráneas
            $table->foreignId('room_id')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->string('coin', 20);  
            $table->decimal('ctn_price', 10, 2)->comment('Precio del cartón de bingo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_ctn_prices');
    }
};
