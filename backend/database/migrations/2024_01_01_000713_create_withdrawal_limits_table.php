<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla withdrawal_limits: configura los topes de retiro por moneda y
 * nivel de KYC.  Los valores por defecto reflejan una política conservadora
 * hasta que el usuario verifique su identidad.
 *
 * kyc_level  0 = sin verificar  (solo puede retirar un monto simbólico)
 *            1 = email + teléfono verificados
 *            2 = documento de identidad verificado (KYC completo)
 *
 * Los montos están expresados en la moneda referenciada por currency_id.
 * Si no existe un registro para currency_id + kyc_level, se usan los
 * límites del registro con currency_id IS NULL (límite global de fallback).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_limits', function (Blueprint $table) {
            $table->id();

            // NULL = aplica a todas las monedas (fallback global)
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            // 0 = sin KYC, 1 = básico, 2 = completo
            $table->unsignedTinyInteger('kyc_level')->default(0);

            // Límites por ventana temporal (en unidades de la moneda)
            $table->decimal('max_per_transaction', 20, 8)->default(100);
            $table->decimal('max_daily', 20, 8)->default(200);
            $table->decimal('max_weekly', 20, 8)->default(500);
            $table->decimal('max_monthly', 20, 8)->default(1000);
            // Monto mínimo por retiro
            $table->decimal('min_per_transaction', 20, 8)->default(1);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['currency_id', 'kyc_level']);
            $table->index('kyc_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_limits');
    }
};
