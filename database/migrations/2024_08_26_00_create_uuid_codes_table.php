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
        Schema::create('uuid_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code_uuid', 36);
            $table->foreignId('user')->constrained('users')->onDelete('cascade')->nullable();
            $table->foreignId('user_type')->constrained('user_type')->onDelete('cascade')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uuid_codes');
    }
};
