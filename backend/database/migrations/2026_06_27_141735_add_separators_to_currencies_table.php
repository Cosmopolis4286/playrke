<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->char('decimal_separator', 1)
                ->default(',')
                ->after('decimal_places')
                ->comment('Carácter usado como separador decimal (ej: , para es-VE, . para en-US)');

            $table->char('thousands_separator', 1)
                ->default('.')
                ->after('decimal_separator')
                ->comment('Carácter usado como separador de miles (ej: . para es-VE, , para en-US)');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['decimal_separator', 'thousands_separator']);
        });
    }
};
