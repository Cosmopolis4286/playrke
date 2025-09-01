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
        Schema::create('bingo_room_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('ctns')->comment('Cantidad de cartones');
            $table->decimal('automarker', 8, 2)->nullable()->comment('Automarcador (si aplica)');

            $table->enum('winner', ['0', '1', '2'])->default('0')
                ->comment('0 = no ganador, 1 = primer premio, 2 = segundo premio');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_room_users');
    }
};
