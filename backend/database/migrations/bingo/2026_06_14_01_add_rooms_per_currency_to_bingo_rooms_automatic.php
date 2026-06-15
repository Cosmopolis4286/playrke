<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->boolean('rooms_per_currency')
                ->default(false)
                ->after('allowed_currencies')
                ->comment('Si es true, genera una sala independiente por cada moneda permitida');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->dropColumn('rooms_per_currency');
        });
    }
};
