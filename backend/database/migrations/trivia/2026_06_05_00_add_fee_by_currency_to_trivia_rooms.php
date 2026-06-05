<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'pgsql_trivia';
    }

    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            // Precio de entrada por moneda: { "currency_id": amount, ... }
            // Permite salas multidivisa (Bs + COP, etc.) igual que Bingo.
            // Cuando está vacío o null, se usa entry_fee + entry_currency_id (legado).
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'fee_by_currency')) {
                $table->jsonb('fee_by_currency')->nullable()->after('entry_currency_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->dropColumnIfExists('fee_by_currency');
        });
    }
};
