<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bingo_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('encrypted', 18)->unique();
            $table->foreignId('status')->constrained('game_status')->onDelete('cascade')->default(1);
            $table->enum('room_type', ['PV', 'PB'])->default('PV');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade')->nullable();
            $table->foreignId('user')->constrained('users')->onDelete('cascade');
            $table->boolean('paid_win')->default(0);
            $table->timestamp('publication_date')->nullable();
            $table->integer('players')->default(0);
            $table->integer('ctns')->default(0);
            $table->double('all_sale')->default(0);
            $table->double('room_commn')->default(0);
            $table->double('seller_commn')->default(0);
            $table->double('jackpot')->default(0);
            $table->double('jackpot_prize')->default(0);
            $table->double('serial_prize')->default(0);
            $table->integer('close_room')->nullable();
            $table->string('code_uuid', 36)->nullable()->unique();
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
        Schema::table('bingo_rooms', function (Blueprint $table) {
            $table->dropForeign(['status', 'creator_user']);
        });

        Schema::dropIfExists('bingo_rooms');
    }
};
