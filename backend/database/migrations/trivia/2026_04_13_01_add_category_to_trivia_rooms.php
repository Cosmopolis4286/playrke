<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'category')) {
                $table->string('category', 50)->nullable()->after('description');
            }
        });

        \Illuminate\Support\Facades\DB::connection('pgsql_trivia')->statement(
            'CREATE INDEX IF NOT EXISTS trivia_rooms_category_index ON trivia_rooms (category)'
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            if (Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
