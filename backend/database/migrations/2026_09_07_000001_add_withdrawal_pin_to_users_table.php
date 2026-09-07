<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users ya tiene datos reales — migración incremental, mismo patrón que
 * 2026_09_02_000001_add_registration_ip_to_users_table y
 * 2026_08_31_000100_add_pending_debt_to_wallets_table.
 *
 * PIN de retiro (4-6 dígitos): segunda credencial exigida por
 * WalletService::requestWithdraw() ANTES de descontar el saldo, separada
 * del password de login — protege contra un retiro hecho desde una sesión
 * comprometida (dispositivo compartido, XSS puntual) sin exigir reautenticar
 * el login completo en cada retiro.
 *
 * withdrawal_pin_hash: Hash::make() del PIN, igual que 'password' — nunca en
 * texto plano. Nullable: el PIN es opt-in, no todos los usuarios lo fijan de
 * entrada (aunque WalletController puede exigirlo antes de habilitar retiros
 * si el negocio decide hacerlo obligatorio más adelante).
 *
 * withdrawal_pin_set_at: fecha del último cambio, mostrada en el perfil
 * ("PIN configurado el ...") y usada para detectar si el PIN nunca se ha
 * fijado sin depender de que el hash sea null (defensa en profundidad).
 *
 * El Schema::create original (2024_01_01_000101_create_users_table.php)
 * también queda actualizado con estas columnas para que un entorno nuevo
 * (migrate:fresh) las tenga desde la creación.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'withdrawal_pin_hash')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('withdrawal_pin_hash', 255)
                ->nullable()
                ->after('password')
                ->comment('Hash (Hash::make) del PIN de retiro — segunda credencial exigida antes de descontar saldo en un retiro, independiente del password de login');

            $table->timestamp('withdrawal_pin_set_at')
                ->nullable()
                ->after('withdrawal_pin_hash')
                ->comment('Fecha del último cambio de withdrawal_pin_hash');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'withdrawal_pin_hash')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['withdrawal_pin_hash', 'withdrawal_pin_set_at']);
        });
    }
};
