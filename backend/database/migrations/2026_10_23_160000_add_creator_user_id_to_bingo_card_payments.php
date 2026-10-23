<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El modelo y el flujo de revisión de pagos usan creator_user_id,
     * pero la migración original de bingo_card_payments no lo creaba.
     * Se agrega de forma nullable para no invalidar pagos históricos.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bingo_card_payments', 'creator_user_id')) {
            Schema::table('bingo_card_payments', function (Blueprint $table) {
                $table->foreignSnowflake('creator_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->nullOnDelete()
                    ->comment('Usuario creador de la sala al registrar el pago');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bingo_card_payments', 'creator_user_id')) {
            Schema::table('bingo_card_payments', function (Blueprint $table) {
                $table->dropForeign(['creator_user_id']);
                $table->dropColumn('creator_user_id');
            });
        }
    }
};
