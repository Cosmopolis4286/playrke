<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos subidos para el proceso de verificación KYC.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->snowflake('id')->primary();
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // 'dni' | 'passport' | 'driving_license' | 'residence_permit'
            $table->string('document_type', 30);
            // Número del documento (encriptado en capa de aplicación si se requiere)
            $table->string('document_number', 50)->nullable();
            // Rutas de archivos en storage (frente y dorso)
            $table->string('front_path', 500);
            $table->string('back_path', 500)->nullable();
            // 'selfie' opcional para liveness check
            $table->string('selfie_path', 500)->nullable();

            // 'pending' | 'approved' | 'rejected'
            $table->string('status', 20)->default('pending')->index();
            $table->string('rejection_reason', 500)->nullable();

            $table->foreignSnowflake('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_kyc_documents_user');
            $table->index('status', 'idx_kyc_documents_status');
            $table->index('reviewed_by', 'idx_kyc_documents_reviewer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};
