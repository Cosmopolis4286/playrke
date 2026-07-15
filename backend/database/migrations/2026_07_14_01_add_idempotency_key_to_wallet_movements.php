<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * idempotency_key: identificador determinístico opcional para prevenir doble
 * acreditación en pagos que pueden reintentarse (ej. WalletService::recordPrize()
 * llamado por trivia:retry-failed-prize-payouts). Separada de reference_id
 * porque ese campo ya se reutiliza legítimamente pasadas 24h en depósitos
 * (ver WalletFraudService::checkDuplicateReference()) — un unique ahí
 * rompería ese flujo.
 *
 * Nullable + unique: en MySQL múltiples NULL no violan la restricción, así
 * que los movimientos existentes y los flujos que no la usan no se afectan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('wallet_movements', 'idempotency_key')) {
                $table->string('idempotency_key', 100)->nullable()->unique()->after('reference_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            if (Schema::hasColumn('wallet_movements', 'idempotency_key')) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            }
        });
    }
};
