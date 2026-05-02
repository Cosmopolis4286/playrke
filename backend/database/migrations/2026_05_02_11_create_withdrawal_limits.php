<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        // Seed: límites globales por nivel KYC (currency_id NULL = todas las monedas)
        DB::table('withdrawal_limits')->insert([
            // Sin KYC: límites muy conservadores
            ['currency_id' => null, 'kyc_level' => 0, 'min_per_transaction' => 1,  'max_per_transaction' => 50,    'max_daily' => 50,    'max_weekly' => 100,   'max_monthly' => 200,   'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // KYC básico (email + teléfono)
            ['currency_id' => null, 'kyc_level' => 1, 'min_per_transaction' => 1,  'max_per_transaction' => 500,   'max_daily' => 1000,  'max_weekly' => 3000,  'max_monthly' => 10000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // KYC completo (documento)
            ['currency_id' => null, 'kyc_level' => 2, 'min_per_transaction' => 1,  'max_per_transaction' => 5000,  'max_daily' => 10000, 'max_weekly' => 30000, 'max_monthly' => 100000,'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_limits');
    }
};
