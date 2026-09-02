<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IP capturada en RegisteredUserController::store() — base para el
     * rate-limit de cuentas nuevas por IP/día y para auditar sybil farming
     * del sistema de referidos (varias cuentas con la misma IP referidas
     * por el mismo usuario).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->ipAddress('registration_ip')->nullable()->after('last_login_ip')->comment('IP desde la que se registró la cuenta');
            $table->index('registration_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['registration_ip']);
            $table->dropColumn('registration_ip');
        });
    }
};
