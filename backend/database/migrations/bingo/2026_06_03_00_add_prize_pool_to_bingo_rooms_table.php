<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('bingo_rooms', 'prize_pool')) {
                $table->decimal('prize_pool', 15, 2)->default(0)->after('paid_prizes')
                    ->comment('Pool de premios de la sala');
            }
            if (!Schema::hasColumn('bingo_rooms', 'full_house_prize')) {
                $table->decimal('full_house_prize', 15, 2)->default(0)->after('prize_pool')
                    ->comment('Premio por línea completa');
            }
            if (!Schema::hasColumn('bingo_rooms', 'serial_prize')) {
                $table->decimal('serial_prize', 15, 2)->default(0)->after('full_house_prize')
                    ->comment('Premio por serial');
            }
            if (!Schema::hasColumn('bingo_rooms', 'rke_commission')) {
                $table->decimal('rke_commission', 8, 2)->default(0)->after('serial_prize')
                    ->comment('Comisión del RKE');
            }
            if (!Schema::hasColumn('bingo_rooms', 'room_commission')) {
                $table->decimal('room_commission', 8, 2)->default(0)->after('rke_commission')
                    ->comment('Comisión de la sala');
            }
            if (!Schema::hasColumn('bingo_rooms', 'seller_commission')) {
                $table->decimal('seller_commission', 8, 2)->default(0)->after('room_commission')
                    ->comment('Comisión del vendedor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms', function (Blueprint $table) {
            $table->dropColumn([
                'prize_pool', 'full_house_prize', 'serial_prize',
                'rke_commission', 'room_commission', 'seller_commission',
            ]);
        });
    }
};
