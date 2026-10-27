<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('user_suspensions');
    }

    public function up() : void
    {
        Schema::create('user_suspensions', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único de la suspensión');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario suspendido');

            $table->string('origin', 32)->default('anti_cheat')->comment('anti_cheat | manual_admin');
            $table->string('reason', 255);
            $table->text('notes')->nullable();

            $table->foreignSnowflake('triggering_flag_id')->nullable()
                ->constrained('anti_cheat_flags')->nullOnDelete()
                ->comment('Flag que cruzó el umbral, si origin=anti_cheat');

            $table->foreignSnowflake('suspended_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete()
                ->comment('Admin que suspendió manualmente; null si fue automático');

            $table->timestamp('expires_at')->nullable()->comment('null = indefinida hasta revisión manual');

            $table->timestamp('resolved_at')->nullable();
            $table->foreignSnowflake('resolved_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'resolved_at'], 'idx_suspensions_user_active');
            $table->index('origin');
        });
    }
};
