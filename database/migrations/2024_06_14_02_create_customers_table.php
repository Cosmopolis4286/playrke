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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->string('description', 45)->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('customer_code', 30)->nullable()->unique();
            $table->string('phone', 12)->nullable();
            $table->double('commn')->nullable();
            $table->boolean('all_in')->default(false);
            $table->string('brand', 25)->nullable();
            $table->string('product', 25)->nullable();
            $table->string('logo', 35)->nullable();
            $table->string('link_route', 255)->nullable();
            $table->string('token', 36)->nullable()->unique();
            $table->text('customer_key')->nullable();
            $table->text('security_key')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
