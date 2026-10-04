<?php

declare(strict_types=1);

use App\Models\MovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conciliación financiera de Bingo:
 *
 *  - platform_receivables: cuentas por cobrar de la plataforma. En ventas
 *    fuera del sistema (vendedor con comprobante / comercio en mano) el dinero
 *    lo tiene el comercio, así que la comisión RKE ya no se acredita a la
 *    wallet de plataforma al vender — queda como deuda del comercio y se
 *    acredita recién al marcarla cobrada (PlatformReceivableService::settle()).
 *
 *  - financial_reconciliation_items: alertas que requieren conciliación
 *    manual (reversa de RKE/jackpot que no pudo completarse, pago al creador
 *    fallido…). Antes solo quedaban en el log de errores.
 *
 *  - movement_types CREATOR_COMMISSION: acreditación al creador de la sala de
 *    su comisión (y fondos del premio en Premio/Servicio puras) al cerrarse la
 *    sala, por las ventas pagadas con billetera (BingoRoomCreatorPayoutService).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('platform_receivables')) {
            Schema::create('platform_receivables', function (Blueprint $table) {
                $table->snowflake('id')->primary();
                $table->string('source', 30)->default('BINGO_RKE')->comment('Origen de la deuda');
                $table->foreignSnowflake('debtor_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate()
                    ->comment('Comercio que tiene el dinero y le debe a la plataforma');
                $table->foreignSnowflake('currency_id')->constrained('currencies')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignSnowflake('room_id')->nullable()->constrained('bingo_rooms')->nullOnDelete()->cascadeOnUpdate();
                // unsignedBigInteger, NO snowflake(): el macro de dive-be/laravel-snowflake
                // es unsignedBigInteger($column)->primary() — solo sirve para 'id'. En
                // otra columna declara una segunda PK ("Multiple primary key defined").
                $table->unsignedBigInteger('lot_id')->nullable()->unique()->comment('Lote de cartones que originó la deuda (una por lote)');
                $table->decimal('amount', 13, 2)->comment('Total = rke + jackpot + prize_pool + creator');
                // Desglose: la red (vendedor/agencia/comercializadora) y el creador se
                // quedan con sus comisiones; esto es lo que el comercio retuvo y debe.
                $table->decimal('rke_amount', 13, 2)->default(0)->comment('Comisión RKE de la plataforma');
                $table->decimal('jackpot_amount', 13, 2)->default(0)->comment('Aporte al jackpot (lo acumula la plataforma)');
                $table->decimal('prize_pool_amount', 13, 2)->default(0)->comment('Parte del bote (la plataforma paga los premios a billeteras)');
                // Venta cruzada (sala PÚBLICA de otro creador): comisión del creador
                // que retuvo el comercio vendedor. La plataforma la custodia y se la
                // paga al creador cuando la sala cerró y la deuda está cobrada.
                $table->decimal('creator_amount', 13, 2)->default(0)->comment('Comisión del creador de la sala (venta cruzada)');
                $table->foreignSnowflake('creator_payee_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate()
                    ->comment('Creador de la sala que debe recibir creator_amount');
                $table->timestamp('creator_paid_at')->nullable()->comment('Cuándo se le pagó al creador');
                $table->enum('status', ['PENDING', 'SETTLED', 'CANCELLED'])->default('PENDING');
                $table->timestamp('settled_at')->nullable();
                $table->foreignSnowflake('settled_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('cancelled_at')->nullable()->comment('Lote reembolsado: la deuda ya no aplica');
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->index(['status', 'debtor_user_id', 'currency_id'], 'idx_platform_receivables_status_debtor');
            });
        }

        if (!Schema::hasTable('financial_reconciliation_items')) {
            Schema::create('financial_reconciliation_items', function (Blueprint $table) {
                $table->snowflake('id')->primary();
                $table->string('type', 40)->comment('RKE_REVERSAL_FAILED, JACKPOT_REVERSAL_SHORTFALL, CREATOR_PAYOUT_FAILED…');
                $table->enum('status', ['OPEN', 'RESOLVED'])->default('OPEN');
                $table->foreignSnowflake('currency_id')->nullable()->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();
                $table->decimal('amount', 15, 4)->default(0);
                $table->foreignSnowflake('room_id')->nullable()->constrained('bingo_rooms')->nullOnDelete()->cascadeOnUpdate();
                $table->unsignedBigInteger('lot_id')->nullable(); // ver nota en platform_receivables.lot_id
                $table->string('idempotency_key', 120)->nullable()->unique();
                $table->string('message', 255);
                $table->json('details')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignSnowflake('resolved_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->string('resolution_notes', 255)->nullable();
                $table->timestamps();

                $table->index(['status', 'type'], 'idx_fin_reconciliation_status_type');
            });
        }

        MovementType::firstOrCreate(
            ['code' => 'CREATOR_COMMISSION'],
            [
                'label'             => 'Comisión de sala (creador)',
                'icon'              => 'fas fa-hand-holding-dollar',
                'color'             => '#059669',
                'is_credit'         => true,
                'requires_approval' => false,
                'game_group'        => 'BINGO',
                'sort_order'        => 66,
                'visible_roles'     => null,
                'is_system'         => true,
                'status'            => 'ACTIVE',
            ],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reconciliation_items');
        Schema::dropIfExists('platform_receivables');

        MovementType::where('code', 'CREATOR_COMMISSION')
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('wallet_movements')->whereColumn('wallet_movements.type', 'movement_types.code'))
            ->delete();
    }
};
