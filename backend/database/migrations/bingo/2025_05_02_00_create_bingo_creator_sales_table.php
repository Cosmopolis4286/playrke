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
            // PK con Snowflake
            $table->snowflake('id')->primary();;
            $table->foreignSnowflake('room_id')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignSnowflake('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignSnowflake('currency_id')->constrained('currencies')
                ->cascadeOnUpdate()->cascadeOnDelete()
                ->comment('ID moneda asociada');
            $table->decimal('sale_amount', 12, 2);
            $table->decimal('rke_commission', 6, 2);
            $table->decimal('room_commission', 6, 2);
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
