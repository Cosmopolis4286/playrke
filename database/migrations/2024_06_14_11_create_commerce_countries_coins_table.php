<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerces_countries_coins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('commerce')->constrained('commerces')->onDelete('cascade');
            $table->foreignId('country')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('coin')->constrained('coins')->onDelete('cascade');

            $table->enum('level', ['1', '2', '3']);
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade');

            $table->boolean('status')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerces_countries_coins');
    }
};
