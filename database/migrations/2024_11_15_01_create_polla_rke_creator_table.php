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
        Schema::create('polla_rke_creator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw')->constrained('polla_rke')->onDelete('cascade'); 
            $table->foreignId('creator_type')->constrained('user_type')->onDelete('cascade'); 
            $table->integer('creator_id');
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polla_rke_creator');
    }
};
