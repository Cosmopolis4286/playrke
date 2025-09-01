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
        Schema::create('bingo_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('encrypted', 18)->unique(); // Identificador encriptado único
            $table->unsignedBigInteger('status_id')->default(1); // FK a game_status
            $table->enum('room_type', ['PV', 'PB'])->default('PV'); // Tipo de sala
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('paid_win')->default(false);
            $table->timestamp('publication_date')->nullable();
            $table->integer('players')->default(0);
            $table->integer('ctns')->default(0);

            // Campos financieros
            $table->double('all_sale', 15, 2)->default(0);
            $table->double('rke_commn', 15, 2)->default(0);
            $table->double('room_commn', 15, 2)->default(0);
            $table->double('seller_commn', 15, 2)->default(0);
            $table->double('prize_pool', 15, 2)->default(0);
            $table->double('full_house_prize', 15, 2)->default(0);
            $table->double('serial_prize', 15, 2)->default(0);

            $table->integer('close_room')->nullable();
            $table->string('code_uuid', 36)->nullable()->unique();
            $table->timestamps();

            // Foreign key
            $table->foreign('status_id')->references('id')->on('game_status')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rooms');
    }
};
