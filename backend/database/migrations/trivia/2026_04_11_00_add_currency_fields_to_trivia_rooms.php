<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'entry_currency_type')) {
                $table->string('entry_currency_type', 20)->default('fixed')->after('time_per_question');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'entry_fee')) {
                $table->decimal('entry_fee', 20, 8)->default(0)->after('entry_currency_type');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'entry_currency_id')) {
                $table->ulid('entry_currency_id')->nullable()->after('entry_fee');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'local_currency_id')) {
                $table->ulid('local_currency_id')->nullable()->after('entry_currency_id');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'entry_fee_usd')) {
                $table->decimal('entry_fee_usd', 20, 8)->default(0)->after('local_currency_id');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'prize_type')) {
                $table->string('prize_type', 20)->default('accumulated')->after('entry_fee_usd');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'prize_amount')) {
                $table->decimal('prize_amount', 20, 8)->default(0)->after('prize_type');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'prize_currency_id')) {
                $table->ulid('prize_currency_id')->nullable()->after('prize_amount');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'commission_percent')) {
                $table->decimal('commission_percent', 5, 2)->default(0)->after('prize_currency_id');
            }
        });

        $conn = DB::connection('pgsql_trivia');
        $conn->statement('CREATE INDEX IF NOT EXISTS trivia_rooms_entry_currency_type_index ON trivia_rooms (entry_currency_type)');
        $conn->statement('CREATE INDEX IF NOT EXISTS trivia_rooms_entry_currency_id_index ON trivia_rooms (entry_currency_id)');
        $conn->statement('CREATE INDEX IF NOT EXISTS trivia_rooms_prize_type_index ON trivia_rooms (prize_type)');
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->dropColumn([
                'entry_currency_type',
                'entry_fee',
                'entry_currency_id',
                'local_currency_id',
                'entry_fee_usd',
                'prize_type',
                'prize_amount',
                'prize_currency_id',
                'commission_percent',
            ]);
        });
    }
};
