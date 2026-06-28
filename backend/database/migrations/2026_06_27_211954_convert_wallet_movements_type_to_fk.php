<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $orphanTypes = DB::table('wallet_movements')
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->diff(DB::table('movement_types')->pluck('code'));

        if ($orphanTypes->isNotEmpty()) {
            throw new \RuntimeException(
                'No se puede convertir wallet_movements.type a FK: existen tipos sin catálogo en movement_types: '
                . $orphanTypes->implode(', ')
            );
        }

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->string('type', 30)->change();
        });

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->foreign('type')
                ->references('code')->on('movement_types')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropForeign(['type']);
        });

        DB::statement("ALTER TABLE wallet_movements MODIFY COLUMN type ENUM('DEPOSIT','WITHDRAWAL','PURCHASE','PRIZE','TRIVIA_ENTRY','RKE_COMMISSION') NOT NULL COMMENT 'Tipo de movimiento'");
    }
};
