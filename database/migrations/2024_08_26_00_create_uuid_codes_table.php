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
        Schema::create('unique_codes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid_code', 36)->unique();
            $table->string('rke_code', 25)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->nullable();
            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unique_codes');
    }
};
