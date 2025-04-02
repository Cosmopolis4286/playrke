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
        Schema::create('polla_draws', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('game_type')->constrained('polla_types')->onDelete('cascade');
            $table->unsignedBigInteger('status')->default(2);
            $table->enum('room_type', ['PV', 'PB']);
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade')->nullable();
            $table->boolean('paid_win')->default(0);
            $table->timestamp('publication_date')->nullable();
            $table->integer('players')->default(0);
            $table->integer('plays')->default(0);
            $table->integer('close_sale');
            $table->double('all_sale')->default(0);
            $table->double('rke_commn')->default(0)->nullable();
            $table->double('creator_commn')->default(0)->nullable();
            $table->double('seller_commn')->default(0)->nullable();
            $table->double('jackpot')->default(0);
            $table->string('code_uuid', 36)->nullable()->unique();
            $table->timestamps();

            // Definición de las claves foráneas
            $table->foreign('status')->references('id')->on('game_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polla_draws');
    }
};
