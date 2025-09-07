<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colombian_payment_accounts', function (Blueprint $table) {
            $table->id();

            $table->enum('gateway', ['BANCOLOMBIA', 'DAVIPLATA', 'NEQUI'])
                ->index()
                ->comment('Pasarela de pago en Colombia');

            $table->string('user_name', 60)->nullable()->comment('Nombre del titular de la cuenta');
            $table->string('account_number', 25)->nullable()->index();
            $table->string('document_number', 20)->nullable()->comment('Número de documento asociado');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');

            $table->timestamps();

            // Evitar que un mismo usuario registre la misma cuenta/gateway más de una vez
            $table->unique(['user_id', 'gateway', 'account_number'], 'uniq_user_gateway_acc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colombian_payment_accounts');
    }
};
