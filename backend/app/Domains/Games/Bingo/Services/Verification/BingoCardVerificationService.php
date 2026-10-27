<?php

namespace App\Domains\Games\Bingo\Services\Verification;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\ExternalPlayerParticipation;
use App\Models\Shared\PhoneNumber;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Verificación pública de un cartón por serial + últimos 4 dígitos del
 * teléfono del comprador (segundo factor liviano, evita que cualquiera con
 * el serial vea si ese cartón ganó un premio).
 *
 * El comprador de un BingoCard es o un User con cuenta (creator_user_id) o
 * un ExternalPlayer sin cuenta, vinculado vía ExternalPlayerParticipation
 * (ver BingoGameService::getUserCartonsForExternalPlayer()) — nunca ambos.
 */
final class BingoCardVerificationService
{
    public function verify(string $serialNumber, string $phoneLast4) : array
    {
        $card = BingoCard::where('serial_number', $serialNumber)
            ->with(['room.config', 'prizes' => fn ($q) => $q->latest('created_at')])
            ->first();

        if (! $card || ! $this->phoneMatches($card, $phoneLast4)) {
            throw new AuthorizationException('Cartón o teléfono no coinciden.');
        }

        return $this->toPayload($card);
    }

    private function externalPlayerPhone(BingoCard $card) : ?string
    {
        $externalPlayerId = ExternalPlayerParticipation::ofParticipable(BingoCard::class)
            ->where('participable_id', (string) $card->id)
            ->value('external_player_id');

        if (! $externalPlayerId) {
            return null;
        }

        return ExternalPlayer::find($externalPlayerId)?->phone;
    }

    private function phoneMatches(BingoCard $card, string $phoneLast4) : bool
    {
        $phone = $card->creator_user_id
            ? PhoneNumber::where('user_id', $card->creator_user_id)->active()->value('phone_number')
            : $this->externalPlayerPhone($card);

        if (! $phone) {
            return false;
        }

        $cleanPhone = preg_replace('/[^\d]/', '', $phone);

        return substr($cleanPhone, -4) === $phoneLast4;
    }

    private function toPayload(BingoCard $card) : array
    {
        $prize = $card->prizes->first();

        return [
            'serial_number' => $card->serial_number,
            'card_numbers' => $card->card_numbers ?? [],
            'status' => $card->status,
            'status_label' => $card->getCardStatusDescription(),
            'room' => [
                'name' => $card->room?->uuid_code,
                'date' => $card->room?->config?->date?->format('d/m/Y'),
                'time' => $card->room?->config?->start_time?->format('H:i'),
            ],
            'prize' => $prize ? [
                'type_label' => $prize->prize_type_description,
                'status_label' => $prize->prize_status_description,
                'amount_usd' => (float) $prize->usd_amount,
            ] : null,
        ];
    }
}
