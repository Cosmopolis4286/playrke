<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            // Toda consulta de listado ordena por latest('created_at').
            $table->index('created_at');

            // Caso de uso dominante: "historial de esta entidad" (deep-link
            // desde /admin/users, etc.) — filtra por auditable_type y ordena
            // por created_at. El índice individual de auditable_type ya
            // existente solo cubre parcialmente ese patrón.
            $table->index(['auditable_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['auditable_type', 'created_at']);
        });
    }
};
