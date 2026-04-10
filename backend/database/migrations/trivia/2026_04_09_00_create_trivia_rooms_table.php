<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 10)->unique();
            $table->uuid('host_id');
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->enum('status', ['waiting', 'playing', 'finished', 'cancelled'])->default('waiting');
            $table->integer('max_players')->default(10);
            $table->integer('question_count')->default(10);
            $table->integer('time_per_question')->default(15);
            $table->jsonb('config')->nullable(); // Configuración personalizada (jsonb para PostgreSQL)
            $table->jsonb('metadata')->nullable(); // Datos adicionales (jsonb para PostgreSQL)
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('code');
            $table->index('status');
            $table->index('host_id');
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_rooms');
    }
};
