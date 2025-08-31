<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_players', function (Blueprint $table) {
            $table->id();
            $table->string('link', 25)->unique(); // Asegura que no haya links repetidos

            // Relaciones
            $table->foreignId('game')->constrained('games');
            $table->integer('room');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade');
            $table->foreignId('country')->constrained('countries');
            $table->foreignId('coin')->constrained('coins');
            $table->foreignId('seller')->nullable()->constrained('sellers');
            $table->foreignId('purchase_code')->constrained('user_purchase_codes');

            // Datos del jugador externo
            $table->integer('room_user');
            $table->string('name', 20);
            $table->string('lastname', 20);
            $table->string('identification', 12)->nullable();
            $table->string('phone', 14);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('external_players');
    }
};
