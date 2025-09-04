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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('coin_id')->constrained('coins');
            $table->string('phone', 14);
            $table->double('commn');
            $table->foreignId('commerce_id')->nullable()->constrained('commerces');
            $table->foreignId('subsidiary_id')->nullable()->constrained('subsidiaries')->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active')
                ->comment('Estado activo/inactivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
