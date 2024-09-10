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
        Schema::create('customers_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer');
            $table->string('coin', 15);
            $table->double('exchange_rate')->default(1);
            $table->boolean('status')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('customer')->references('id')->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers_exchange_rates');
    }
};
