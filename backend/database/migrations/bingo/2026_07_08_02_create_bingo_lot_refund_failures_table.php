<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_lot_refund_failures', function (Blueprint $table) {
            $table->snowflake('id')->primary();
            $table->foreignSnowflake('room_id')->constrained('bingo_rooms')->cascadeOnDelete();
            $table->foreignSnowflake('lot_id')->constrained('bingo_card_lots')->cascadeOnDelete();
            $table->foreignSnowflake('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignSnowflake('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->decimal('amount', 18, 4);
            $table->text('error_message');
            $table->enum('status', ['PENDING', 'RESOLVED', 'ABANDONED'])->default('PENDING');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'attempts'], 'idx_lot_refund_failures_status');
            $table->index('room_id', 'idx_lot_refund_failures_room');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_lot_refund_failures');
    }
};
