<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            // Categoría temática de la sala (misma convención que trivia_questions.category)
            $table->string('category', 50)->nullable()->after('description');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
