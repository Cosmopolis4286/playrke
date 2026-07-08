<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_card_prize_payments', function (Blueprint $table) {
            $table->string('payment_proof', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bingo_card_prize_payments', function (Blueprint $table) {
            $table->string('payment_proof', 500)->nullable(false)->change();
        });
    }
};
