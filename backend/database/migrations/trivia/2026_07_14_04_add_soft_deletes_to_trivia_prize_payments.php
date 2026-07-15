<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega deleted_at a trivia_prize_payments: TriviaPrizePayment extiende
 * BaseModel, que usa SoftDeletes globalmente (ver app/Models/BaseModel.php) —
 * sin esta columna, hasCompletedPayment() (que filtra por deleted_at is null
 * vía el scope automático) falla con "column does not exist".
 */
return new class extends Migration
{
    public function up(): void
    {
        $conn = 'pgsql_trivia';

        Schema::connection($conn)->table('trivia_prize_payments', function (Blueprint $table) use ($conn) {
            if (!Schema::connection($conn)->hasColumn('trivia_prize_payments', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        $conn = 'pgsql_trivia';

        Schema::connection($conn)->table('trivia_prize_payments', function (Blueprint $table) use ($conn) {
            if (Schema::connection($conn)->hasColumn('trivia_prize_payments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
