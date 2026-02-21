<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rounds', function (Blueprint $table) {
            $table->foreignSnowflake('prize_id')->nullable()->after('prize_value')
                ->comment('Premio físico/servicio seleccionado (solo cuando prize_type = OTHER)');

            $table->foreign('prize_id')
                ->references('id')
                ->on('prizes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rounds', function (Blueprint $table) {
            $table->dropForeign(['prize_id']);
            $table->dropColumn('prize_id');
        });
    }
};
