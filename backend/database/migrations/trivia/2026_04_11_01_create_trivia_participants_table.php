<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_participants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('room_id');
            $table->foreign('room_id')->references('id')->on('trivia_rooms')->onDelete('cascade');
            
            $table->ulid('user_id'); // Referencia externa a users (MySQL)
            $table->index('user_id');
            
            // Estado del participante
            $table->string('status', 20)->default('registered'); // registered, confirmed, playing, eliminated, winner, abandoned
            
            // Pago/Inscripción
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('confirmed_at')->nullable(); // Cuando pagó/confirmó entrada
            $table->decimal('paid_amount', 20, 8)->default(0);
            $table->ulid('paid_currency_id')->nullable();
            $table->decimal('paid_amount_usd', 20, 8)->default(0); // Para estadísticas
            
            // Juego
            $table->timestamp('joined_game_at')->nullable();
            $table->timestamp('eliminated_at')->nullable();
            $table->unsignedInteger('final_rank')->nullable();
            $table->decimal('prize_won', 20, 8)->default(0);
            
            // Comodines/Poderes usados (snapshot al inicio + modificaciones)
            $table->jsonb('power_ups')->nullable(); // { "half": 2, "jump": 1, "freeze": 0 }
            $table->jsonb('power_ups_used')->nullable(); // { "half": 1, "jump": 0 }
            
            // Respuestas y puntuación
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('wrong_answers')->default(0);
            $table->unsignedInteger('total_score')->default(0);
            $table->decimal('total_time', 10, 3)->default(0); // Tiempo total en segundos (3 decimales para precisión)
            
            // Metadata adicional
            $table->jsonb('metadata')->nullable(); // IP, dispositivo, etc.
            
            $table->timestamps();
            $table->softDeletes();
            
            // Un usuario solo puede participar una vez por sala
            $table->unique(['room_id', 'user_id']);
            
            $table->index('status');
            $table->index(['room_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_participants');
    }
};
