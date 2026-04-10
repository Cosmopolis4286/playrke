<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Nota: el orden de preguntas en un juego va en game_questions.order_index
            $table->text('question_text');
            $table->string('image_url', 500)->nullable();
            $table->unsignedTinyInteger('correct_option_index'); // Índice 0-255, sin negativos
            $table->jsonb('options'); // ["A", "B", "C", "D"] - Flexible con JSONB
            $table->integer('time_limit_seconds')->default(15);
            $table->integer('points')->default(100);
            $table->text('explanation')->nullable(); // Explicación de la respuesta
            $table->string('category', 50)->nullable();
            $table->string('difficulty', 10)->default('medium'); // easy, medium, hard
            $table->timestamps();

            // Índices críticos para performance
            $table->index(['category', 'difficulty']); // Cubre: category, category+difficulty
            $table->index('created_at'); // Útil para selección aleatoria y últimas preguntas
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_questions');
    }
};
