<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->boolean('rooms_per_currency_split')
                ->default(false)
                ->after('allowed_currencies')
                ->comment('Indica si esta sala fue generada como parte de un split por moneda');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropColumn('rooms_per_currency_split');
        });
    }
};
