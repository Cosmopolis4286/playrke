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
        Schema::create('polla_rke', function (Blueprint $table) {
            $table->id();
            $table->string('n_1', 2);
            $table->string('n_2', 2);
            $table->string('n_3', 2);
            $table->string('n_4', 2);
            $table->string('n_5', 2);
            $table->string('n_6', 2);
            $table->string('n_7', 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polla_rke');
    }
};
