<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserSpecificPermissionGrant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al usuario afectado cuando uno de sus permisos individuales expira
 * automáticamente (job ExpireSpecificPermissionGrantsJob) — transparencia
 * sobre por qué perdió un acceso que antes tenía.
 */
class SpecificPermissionExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly UserSpecificPermissionGrant $grant,
    ) {
    }

    public function content() : Content
    {
        return new Content(
            markdown: 'emails.permissions.specific-permission-expired',
            with: [
                'userName' => trim("{$this->user->first_name} {$this->user->last_name}") ?: $this->user->username,
                'permissionName' => $this->grant->permission_name,
                'grantedAt' => $this->grant->created_at?->format('d/m/Y'),
                'expiredAt' => $this->grant->expires_at?->format('d/m/Y H:i'),
            ],
        );
    }

    public function envelope() : Envelope
    {
        return new Envelope(
            subject: 'Un permiso de tu cuenta ha expirado',
        );
    }
}
