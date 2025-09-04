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

            $table->string('encrypted', 18)->unique()
                ->comment('Identificador encriptado único de la sala');

            $table->foreignId('status_id')
                ->constrained('game_status')
                ->onDelete('cascade')
                ->default(1);

            $table->enum('room_type', ['PV', 'PB'])
                ->default('PV')
                ->comment('Tipo de sala: PV o PB');

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->enum('paid_win', ['Y', 'N'])->default('N')
                ->comment('Indica si el premio fue pagado');

            $table->timestamp('publication_date')->nullable();
            $table->integer('players')->default(0);
            $table->integer('ctns')->default(0);

            // Campos financieros
            $table->decimal('all_sale', 15, 2)->default(0);
            $table->decimal('rke_commn', 15, 2)->default(0);
            $table->decimal('room_commn', 15, 2)->default(0);
            $table->decimal('seller_commn', 15, 2)->default(0);
            $table->decimal('prize_pool', 15, 2)->default(0);
            $table->decimal('full_house_prize', 15, 2)->default(0);
            $table->decimal('serial_prize', 15, 2)->default(0);

            $table->integer('close_room')->nullable();
            $table->string('uuid_code', 36)->nullable()->unique();

            $table->timestamps();
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
