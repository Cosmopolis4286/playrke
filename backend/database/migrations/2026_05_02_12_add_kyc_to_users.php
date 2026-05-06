<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade las columnas de KYC directamente en users y crea la tabla
 * kyc_documents para guardar los documentos subidos.
 *
 * kyc_level  0 = NONE       (sin verificación)
 *            1 = BASIC       (email + teléfono verificados)
 *            2 = FULL        (documento de identidad aprobado)
 *
 * kyc_status solo aplica cuando hay una verificación en curso:
 *   NONE | PENDING | VERIFIED | REJECTED
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('kyc_level')->default(0)->after('tos_accepted_ip');
            $table->string('kyc_status', 20)->default('NONE')->after('kyc_level');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
            $table->string('kyc_rejection_reason', 500)->nullable()->after('kyc_verified_at');
            $table->index('kyc_level');
            $table->index('kyc_status');
        });

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
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['kyc_level']);
            $table->dropIndex(['kyc_status']);
            $table->dropColumn(['kyc_level', 'kyc_status', 'kyc_verified_at', 'kyc_rejection_reason']);
        });
    }
};
