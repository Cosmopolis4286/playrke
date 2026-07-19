<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_card_lots', function (Blueprint $table) {
            // Cuánto de sale_amount se pagó con bonus_balance (no retirable) en
            // vez de balance real. Permite reembolsar proporcionalmente a cada
            // columna si la sala se cancela (ver WalletService::recordRefund()
            // y BingoCardLotRefundJob).
            $table->decimal('bonus_amount', 15, 2)->default(0.00)->after('sale_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_card_lots', function (Blueprint $table) {
            $table->dropColumn('bonus_amount');
        });
    }
};
