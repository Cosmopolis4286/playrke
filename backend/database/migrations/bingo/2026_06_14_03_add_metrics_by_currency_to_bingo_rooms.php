<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms', function (Blueprint $table) {
            $table->json('metrics_by_currency')->nullable()->after('total_sales')
                ->comment('Desglose por moneda: ventas, cartones, bote y comisiones en moneda local');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms', function (Blueprint $table) {
            $table->dropColumn('metrics_by_currency');
        });
    }
};
