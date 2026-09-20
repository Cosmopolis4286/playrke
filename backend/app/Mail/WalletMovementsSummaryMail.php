<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Resumen semanal de movimientos de billetera para admins con
 * wallet.movements.manage — el cuerpo trae solo agregados (aprobado,
 * rechazado, monto por moneda, top pendientes más antiguos); el detalle
 * completo del período va en el CSV adjunto, no en el cuerpo del correo.
 */
class WalletMovementsSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $summary,
        public readonly string $csvContent,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📊 Resumen semanal de movimientos de billetera',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.wallet-movements-summary',
            with: $this->summary,
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->csvContent, 'movimientos-billetera-' . now()->format('Y-m-d') . '.csv')
                ->withMime('text/csv'),
        ];
    }
}
