<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // Código para unirse
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('host_id')->nullable(); // Referencia externa, no FK
            $table->string('status', 20)->default('waiting'); // waiting, playing, closed, cancelled
            $table->integer('max_players')->default(100);
            $table->integer('question_count')->default(10);
            $table->integer('time_per_question')->default(15); // segundos
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('status');
            $table->index('host_id');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_rooms');
    }
};
