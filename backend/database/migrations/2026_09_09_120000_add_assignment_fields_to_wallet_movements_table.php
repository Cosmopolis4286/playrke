<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * assigned_to_user_id/assigned_at permiten que un admin marque "estoy
 * revisando este caso" para que otro admin no trabaje el mismo movimiento
 * pendiente a la vez — lock real (no solo informativo, a diferencia de
 * assigned_to_user_id en system_logs): WalletService::guardNotLockedByOther()
 * rechaza approve()/reject() de otro admin mientras el lock esté vigente.
 * assigned_at también sirve para expirar el lock por inactividad (ver
 * WalletService::ASSIGNMENT_LOCK_MINUTES).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guard de idempotencia — mismo patrón que
        // 2026_09_08_131655_add_receipt_hash_to_wallet_movements_table: sin
        // esto, un migrate:fresh que corre en paralelo con otra sesión que ya
        // deja la columna físicamente creada pero sin registrar en
        // `migrations` revienta con "Column already exists" y aborta toda la
        // corrida de tests antes de llegar al seeder.
        if (Schema::hasColumn('wallet_movements', 'assigned_to_user_id')) {
            return;
        }

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->foreignSnowflake('assigned_to_user_id')
                ->nullable()
                ->after('processed_at')
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate()
                ->comment('Admin que está revisando este movimiento (lock anti-colisión)');

            $table->timestamp('assigned_at')->nullable()
                ->after('assigned_to_user_id')
                ->comment('Fecha en que se tomó el caso — usado para expirar el lock por inactividad');

            $table->index('assigned_to_user_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('wallet_movements', 'assigned_to_user_id')) {
            return;
        }

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropIndex(['assigned_to_user_id']);
            $table->dropColumn(['assigned_to_user_id', 'assigned_at']);
        });
    }
};
