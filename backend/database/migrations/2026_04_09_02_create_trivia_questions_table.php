<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('trivia_rooms')->onDelete('cascade');
            $table->integer('question_number'); // Orden de la pregunta
            $table->text('question_text');
            $table->string('image_url', 500)->nullable();
            $table->string('correct_answer', 255);
            $table->string('option_1', 255);
            $table->string('option_2', 255);
            $table->string('option_3', 255);
            $table->string('option_4', 255)->nullable(); // Opcional
            $table->integer('time_limit_seconds')->default(15);
            $table->integer('points')->default(100);
            $table->text('explanation')->nullable(); // Explicación de la respuesta
            $table->string('category', 50)->nullable();
            $table->string('difficulty', 20)->nullable(); // easy, medium, hard
            $table->timestamps();

            $table->index(['room_id', 'question_number']);
            $table->index('category');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_questions');
    }
};
