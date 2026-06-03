<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Crea la infraestructura para rastrear versiones de Términos de Servicio
 * y la aceptación explícita por parte de cada usuario.
 *
 * tos_versions  →  catálogo de versiones publicadas (versionado semántico)
 * users         →  dos columnas nuevas para el estado actual del usuario
 *
 * Invariantes:
 *  - Solo puede haber una versión 'active' a la vez.
 *  - tos_accepted_version NULL = el usuario nunca aceptó.
 *  - tos_accepted_at NULL = ídem.
 *  - Si tos_accepted_version < versión activa → hay que mostrar nueva versión.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Tabla de versiones ────────────────────────────────────────────────
        if (!Schema::hasTable('tos_versions')) {
            Schema::create('tos_versions', function (Blueprint $table) {
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

            // Insertar la versión inicial
            DB::table('tos_versions')->insert([
                'version'      => '1.0.0',
                'title'        => 'Términos y Condiciones de Uso',
                'content_key'  => 'tos.v1',
                'summary'      => 'Versión inicial de los Términos de Servicio de Playrke.',
                'status'       => 'active',
                'effective_at' => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // ── Columnas en users ─────────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            // Versión de TOS que aceptó (NULL = nunca aceptó)
            $table->string('tos_accepted_version', 20)->nullable()->after('user_preferences');
            // Cuándo aceptó la versión actual
            $table->timestamp('tos_accepted_at')->nullable()->after('tos_accepted_version');
            // IP desde la que aceptó (evidencia legal)
            $table->string('tos_accepted_ip', 45)->nullable()->after('tos_accepted_at');

            $table->index('tos_accepted_version');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tos_accepted_version']);
            $table->dropColumn(['tos_accepted_version', 'tos_accepted_at', 'tos_accepted_ip']);
        });

        Schema::dropIfExists('tos_versions');
    }
};
