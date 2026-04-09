<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            // Path del comprobante de pago (depósitos)
            $table->string('receipt_path', 512)->nullable()->after('reference_id')
                ->comment('Ruta del comprobante de pago (depósitos)');

            // Información adicional en JSON (directorio destino en retiros, notas admin, etc.)
            $table->json('metadata')->nullable()->after('receipt_path')
                ->comment('Datos adicionales: directorio de retiro, notas admin, etc.');

            // Notas del administrador al aprobar/rechazar
            $table->string('admin_notes', 512)->nullable()->after('metadata')
                ->comment('Notas del administrador al procesar el movimiento');

            // Admin que procesó el movimiento
            $table->foreignSnowflake('processed_by')->nullable()->after('admin_notes')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('ID del administrador que procesó el movimiento');

            // Fecha de procesamiento
            $table->timestamp('processed_at')->nullable()->after('processed_by')
                ->comment('Fecha en que el movimiento fue procesado');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->dropColumn(['receipt_path', 'metadata', 'admin_notes', 'processed_by', 'processed_at']);
        });
    }
};
