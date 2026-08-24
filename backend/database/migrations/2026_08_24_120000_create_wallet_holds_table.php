<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reserva de saldo comprometido en una partida en curso, distinta de un
 * WalletMovement (que registra dinero ya movido). Un hold no toca
 * wallets.balance/bonus_balance — solo se descuenta del disponible vía
 * Wallet::getAvailableBalance() mientras status = HELD.
 *
 * game_session_id es una columna simple (no FK) porque la tabla
 * game_sessions todavía no existe (Game Engine, fase posterior de la misma
 * iniciativa) — se agrega la FK física en la migración que cree
 * game_sessions, vía Schema::table().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_holds', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único de la reserva');

            $table->foreignSnowflake('wallet_id')
                ->constrained('wallets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Billetera sobre la que se reserva el saldo');

            $table->unsignedBigInteger('game_session_id')->nullable()->index()
                ->comment('Referencia blanda a game_sessions.id (tabla creada en fase posterior) — sin FK física todavía. unsignedBigInteger para que coincida con el tipo real de snowflake() cuando se agregue la FK.');

            $table->decimal('amount', 15, 2)->comment('Monto reservado (siempre positivo)');

            $table->enum('status', ['HELD', 'CAPTURED', 'RELEASED'])
                ->default('HELD')
                ->comment('HELD: reservado y activo. CAPTURED: se convirtió en WalletMovement de gasto. RELEASED: liberado sin gasto.');

            $table->unsignedBigInteger('captured_movement_id')->nullable()
                ->comment('wallet_movements.id generado al capturar el hold, si aplica');

            $table->string('description', 255)->nullable();

            $table->timestamp('resolved_at')->nullable()
                ->comment('Momento en que pasó a CAPTURED o RELEASED');

            $table->timestamps();

            $table->index(['wallet_id', 'status']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallet_holds ADD CONSTRAINT chk_wallet_holds_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallet_holds DROP CHECK chk_wallet_holds_amount_positive');
        }

        Schema::dropIfExists('wallet_holds');
    }
};
