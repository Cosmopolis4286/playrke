<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Catálogo de versiones publicadas de los Términos de Servicio (versionado semántico).
 *
 * Invariantes:
 *  - Solo puede haber una versión 'active' a la vez.
 *  - tos_accepted_version NULL en `users` = el usuario nunca aceptó.
 *  - Si tos_accepted_version < versión activa → hay que mostrar nueva versión.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('tos_versions');
    }

    public function up() : void
    {
        Schema::create('tos_versions', function(Blueprint $table) {
            $table->id();
            // Semver o fecha: "1.0.0", "2025-05-01"
            $table->string('version', 20)->unique();
            $table->string('title', 200);
            // URL o clave del archivo de contenido; el texto puede vivir en storage
            $table->string('content_key', 200)->nullable();
            // Resumen de cambios para mostrar al usuario
            $table->text('summary')->nullable();
            // 'draft' | 'active' | 'archived'
            $table->string('status', 20)->default('draft')->index();
            // Cuándo entra en vigor (puede ser futura)
            $table->timestamp('effective_at')->useCurrent();
            $table->timestamps();
        });
    }
};
