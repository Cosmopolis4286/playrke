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
        Schema::create('payment_methods_col', function (Blueprint $table) {
            $table->id();
            $table->enum('gateway', ['B', 'D', 'N'])->index();
            $table->string('user_name', 45)->nullable();
            $table->string('acc_number', 15)->nullable()->index();
            $table->string('document_number', 15)->nullable();
            $table->unsignedBigInteger('user');
            $table->timestamps();

            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');

            $table->index('user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods_col');
    }
};
