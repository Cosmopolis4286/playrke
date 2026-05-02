<?php

namespace App\Services;

use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class KycService
{
    public const STATUS_NONE     = 'NONE';
    public const STATUS_PENDING  = 'PENDING';
    public const STATUS_VERIFIED = 'VERIFIED';
    public const STATUS_REJECTED = 'REJECTED';

    public const LEVEL_NONE  = 0;
    public const LEVEL_BASIC = 1;
    public const LEVEL_FULL  = 2;

    /**
     * El usuario envía su documento de identidad para revisión manual.
     * Cambia kyc_status a PENDING y crea el registro KycDocument.
     */
    public function submitDocument(
        User         $user,
        string       $documentType,
        string       $documentNumber,
        UploadedFile $front,
        ?UploadedFile $back = null,
        ?UploadedFile $selfie = null,
    ): KycDocument {
        if (! in_array($documentType, KycDocument::DOCUMENT_TYPES, true)) {
            throw new \DomainException("Tipo de documento inválido: {$documentType}");
        }

        if ($user->kyc_status === self::STATUS_VERIFIED) {
            throw new \DomainException('Tu identidad ya ha sido verificada.');
        }

        // Cancelar documentos anteriores pendientes
        KycDocument::where('user_id', $user->id)
            ->where('status', KycDocument::STATUS_PENDING)
            ->update(['status' => KycDocument::STATUS_REJECTED, 'rejection_reason' => 'Reemplazado por nueva solicitud']);

        $frontPath  = $this->storeFile($front, $user->id, 'front');
        $backPath   = $back   ? $this->storeFile($back,   $user->id, 'back')   : null;
        $selfiePath = $selfie ? $this->storeFile($selfie, $user->id, 'selfie') : null;

        $document = KycDocument::create([
            'user_id'         => (string) $user->id,
            'document_type'   => $documentType,
            'document_number' => $documentNumber,
            'front_path'      => $frontPath,
            'back_path'       => $backPath,
            'selfie_path'     => $selfiePath,
            'status'          => KycDocument::STATUS_PENDING,
        ]);

        $user->update(['kyc_status' => self::STATUS_PENDING]);

        Log::info('[KYC] Documento enviado para revisión', [
            'user_id'     => $user->id,
            'document_id' => $document->id,
            'type'        => $documentType,
        ]);

        return $document;
    }

    /**
     * Admin aprueba un documento → sube kyc_level a FULL (2).
     */
    public function approve(KycDocument $document, User $admin): void
    {
        $user = User::findOrFail($document->user_id);

        $document->update([
            'status'      => KycDocument::STATUS_APPROVED,
            'reviewed_by' => (string) $admin->id,
            'reviewed_at' => now(),
        ]);

        $user->update([
            'kyc_level'      => self::LEVEL_FULL,
            'kyc_status'     => self::STATUS_VERIFIED,
            'kyc_verified_at' => now(),
        ]);

        Log::info('[KYC] Documento aprobado', ['user_id' => $user->id, 'document_id' => $document->id]);
    }

    /**
     * Admin rechaza un documento → kyc_status = REJECTED, kyc_level no cambia.
     */
    public function reject(KycDocument $document, User $admin, string $reason): void
    {
        $user = User::findOrFail($document->user_id);

        $document->update([
            'status'           => KycDocument::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by'      => (string) $admin->id,
            'reviewed_at'      => now(),
        ]);

        $user->update([
            'kyc_status'            => self::STATUS_REJECTED,
            'kyc_rejection_reason'  => $reason,
        ]);

        Log::info('[KYC] Documento rechazado', ['user_id' => $user->id, 'document_id' => $document->id]);
    }

    /**
     * Devuelve el estado KYC del usuario autenticado para el frontend.
     */
    public function statusForUser(User $user): array
    {
        $pending = KycDocument::where('user_id', $user->id)
            ->where('status', KycDocument::STATUS_PENDING)
            ->latest()
            ->first();

        return [
            'kyc_level'        => $user->kyc_level,
            'kyc_status'       => $user->kyc_status,
            'kyc_verified_at'  => $user->kyc_verified_at,
            'rejection_reason' => $user->kyc_rejection_reason,
            'has_pending'      => (bool) $pending,
            'submitted_at'     => $pending?->created_at,
        ];
    }

    private function storeFile(UploadedFile $file, string $userId, string $role): string
    {
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $dest = storage_path("app/private/kyc/{$userId}");

        if (! is_dir($dest)) {
            mkdir($dest, 0750, true);
        }

        $file->move($dest, $name);

        return "kyc/{$userId}/{$name}";
    }
}
