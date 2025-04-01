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
        Schema::create('polla_ticket_price', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw')->constrained('polla_draws')->onDelete('cascade');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade')->nullable();
            $table->string('coin', 15);
            $table->double('ticket_price');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polla_ticket_price');
    }
};
