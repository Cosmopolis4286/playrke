<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_rooms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 10)->unique();
            $table->ulid('host_id'); // External reference (Laravel), indexado abajo
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('waiting'); // Flexible: waiting, playing, finished, cancelled
            $table->unsignedInteger('max_players')->default(10);
            $table->unsignedInteger('question_count')->default(10);
            $table->unsignedInteger('time_per_question')->default(15);
            $table->jsonb('config')->nullable(); // Configuración personalizada (jsonb para PostgreSQL)
            $table->jsonb('metadata')->nullable(); // Datos adicionales (jsonb para PostgreSQL)
            $table->unsignedInteger('current_players')->default(0); // Evita contar en runtime
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
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
