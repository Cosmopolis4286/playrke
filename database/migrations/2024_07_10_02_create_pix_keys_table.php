<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pix_keys', function (Blueprint $table) {
            $table->id();

            $table->string('name', 35)->nullable();

            $table->foreignId('bank_id')
                ->constrained('banks_brasil')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('pix_key_type', ['CPF', 'CNPJ', 'EMAIL', 'PHONE', 'RANDOM'])
                ->comment('Tipo de clave PIX');

            $table->string('pix_key_value', 100);

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            // Evitar duplicados por clave y usuario
            $table->unique(['pix_key_type', 'pix_key_value', 'user_id'], 'uniq_pix_per_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pix_keys');
    }
};
