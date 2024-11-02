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
            $table->string('code', 36);
            $table->unsignedBigInteger('user_type');
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->foreign('user_type')->references('id')->on('user_type')->onUpdate('cascade');
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
