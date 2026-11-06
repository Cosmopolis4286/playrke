<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->string('player_message', 512)->nullable()->after('admin_notes')
                ->comment('Motivo o mensaje visible para el usuario dueño del movimiento');
        });

        // Antes el mismo admin_notes se usaba como motivo de rechazo visible
        // al jugador. Preservamos ese texto en player_message y dejamos
        // admin_notes libre para observaciones exclusivamente internas.
        DB::table('wallet_movements')
            ->where('status', 'REJECTED')
            ->whereNotNull('admin_notes')
            ->update([
                'player_message' => DB::raw('admin_notes'),
                'admin_notes' => null,
            ]);
    }

    public function down(): void
    {
        // Al revertir, restaura el motivo en el campo anterior solo cuando
        // no existe ya una nota interna que deba conservarse.
        DB::table('wallet_movements')
            ->where('status', 'REJECTED')
            ->whereNull('admin_notes')
            ->whereNotNull('player_message')
            ->update(['admin_notes' => DB::raw('player_message')]);

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropColumn('player_message');
        });
    }
};
