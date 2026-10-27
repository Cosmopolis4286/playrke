<?php

namespace App\Domains\Games\Bingo\DTOs\Card\Generate;

/**
 * DTO inmutable para la generación de cartones de Bingo por vendedor.
 */
final class BingoCardGenerateDTO
{
    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    /** Métodos de pago de una venta a jugador externo (ver BingoCardGenerateRequest). */
    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_METHODS = [
        self::PAYMENT_CASH,
        self::PAYMENT_MOBILE_PAYMENT,
        self::PAYMENT_BANK_TRANSFER,
    ];
    public const PAYMENT_MOBILE_PAYMENT = 'mobile_payment';

    public static function fromArray(array $data) : self
    {
        $player = $data['player'] ?? [];

        return new self(
            roomId: (string) ($data['room_id'] ?? ''),
            cardCount: (int) ($data['card_count'] ?? 1),
            countryId: (string) ($data['country_id'] ?? ''),
            playerPhone: (string) ($player['phone'] ?? ''),
            playerName: (string) ($player['first_name'] ?? ''),
            playerLastName: (string) ($player['last_name'] ?? ''),
            receiptNumber: (string) ($data['receipt_number'] ?? ''),
            receiptImage: $data['receipt_image'] ?? null,
            expectedCardPrice: isset($data['expected_card_price']) ? (float) $data['expected_card_price'] : null,
            paymentMethod: (string) ($data['payment_method'] ?? self::PAYMENT_MOBILE_PAYMENT),
        );
    }

    public function __construct(
        public readonly string $roomId,
        public readonly int $cardCount,
        /** Snowflake ID del país, mantenido como string para evitar truncado de bigint. */
        public readonly string $countryId,
        public readonly string $playerPhone,
        public readonly string $playerName,
        public readonly string $playerLastName,
        public readonly string $receiptNumber,
        public readonly ?\Illuminate\Http\UploadedFile $receiptImage,
        /** Precio unitario que vio el vendedor en la vista previa; generate() rechaza la venta si ya no coincide. */
        public readonly ?float $expectedCardPrice = null,
        /** cash | mobile_payment | bank_transfer — efectivo va sin comprobante. */
        public readonly string $paymentMethod = self::PAYMENT_MOBILE_PAYMENT,
    ) {
    }
}
