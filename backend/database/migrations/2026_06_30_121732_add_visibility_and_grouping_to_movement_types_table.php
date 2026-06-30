<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movement_types', function (Blueprint $table) {
            $table->json('visible_roles')->nullable()->after('status')
                ->comment('Prefijos de rol (Role.prefix) que pueden ver este tipo en UI. null/[] = visible para todos los roles');

            $table->string('game_group', 40)->nullable()->after('visible_roles')
                ->comment('Agrupación temática para la UI (ej: BINGO, TRIVIA). null = genérico de billetera');

            $table->integer('sort_order')->default(0)->after('game_group')
                ->comment('Orden de presentación en listados, ascendente');

            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movement_types', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn(['visible_roles', 'game_group', 'sort_order']);
        });
    }
};
