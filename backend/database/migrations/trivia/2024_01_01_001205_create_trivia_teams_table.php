<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_teams');
    }

    public function up() : void
    {
        if (Schema::connection('pgsql_trivia')->hasTable('trivia_teams')) {
            return;
        }

        Schema::connection('pgsql_trivia')->create('trivia_teams', function(Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('room_id', 20);
            $table->foreign('room_id')->references('id')->on('trivia_rooms')->onDelete('cascade');

            $table->string('name', 100);
            $table->string('color', 7)->nullable(); // Hex color para UI
            $table->string('icon', 50)->nullable(); // Icono/emoji del equipo
            $table->unsignedInteger('max_players')->default(10);
            $table->unsignedInteger('current_players')->default(0);

            // Para modo duel donde hay equipo A vs equipo B
            $table->unsignedInteger('team_number')->default(1); // 1, 2, 3...

            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('room_id');
            $table->index(['room_id', 'team_number']);
        });
    }
};
