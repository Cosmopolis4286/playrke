<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignSnowflake('user_id')->constrained()->cascadeOnDelete();
            $table->foreignSnowflake('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('role_id')->nullable(); // rol del usuario dentro de la empresa
            $table->timestamps();

            $table->unique(['user_id', 'company_id']); // evita duplicados
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
