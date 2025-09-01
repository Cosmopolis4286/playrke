<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pago_moviles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type')->constrained('document_type')->cascadeOnUpdate();
            $table->string('document', 15)->index()->comment('Número de documento del titular');
            $table->foreignId('bank_id')->constrained('banks_venezuela')->cascadeOnUpdate();
            $table->string('phone', 15)->index()->comment('Teléfono asociado al pago móvil');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_moviles');
    }
};
