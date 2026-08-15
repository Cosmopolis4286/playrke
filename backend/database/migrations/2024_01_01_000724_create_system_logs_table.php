<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            // PK con Snowflake — mismo patrón que command_logs, tabla de
            // sistema de alto volumen poblada automáticamente por el canal
            // de log 'database' (ver App\Logging\DatabaseLogHandler).
            $table->snowflake('id')->primary();

            $table->string('level', 10)->index();
            // error | warn — solo estos dos niveles se persisten (ver
            // DatabaseLogHandler), info/debug quedan solo en archivo/consola.

            $table->text('message');
            $table->string('context')->nullable();
            $table->text('stack')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
