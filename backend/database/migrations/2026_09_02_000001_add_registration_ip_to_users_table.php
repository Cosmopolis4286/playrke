<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        if (! Schema::hasColumn('users', 'registration_ip')) {
            return;
        }

        Schema::table('users', function(Blueprint $table) {
            $table->dropIndex(['registration_ip']);
            $table->dropColumn('registration_ip');
        });
    }

    /**
     * IP capturada en RegisteredUserController::store() — base para el
     * rate-limit de cuentas nuevas por IP/día y para auditar sybil farming
     * del sistema de referidos (varias cuentas con la misma IP referidas
     * por el mismo usuario).
     */
    public function up() : void
    {
        // Guard de idempotencia — sin esto, un migrate:fresh que corre en
        // paralelo con otra sesión que ya deja la columna físicamente
        // creada pero sin registrar en `migrations` revienta con "Column
        // already exists" y aborta toda la corrida de tests antes de
        // llegar al seeder (mismo problema ya visto con receipt_hash).
        if (Schema::hasColumn('users', 'registration_ip')) {
            return;
        }

        Schema::table('users', function(Blueprint $table) {
            $table->ipAddress('registration_ip')->nullable()->after('last_login_ip')->comment('IP desde la que se registró la cuenta');
            $table->index('registration_ip');
        });
    }
};
