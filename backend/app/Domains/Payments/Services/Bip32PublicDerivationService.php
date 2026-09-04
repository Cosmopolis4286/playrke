<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Exceptions\PaymentsProviderMisconfiguredException;
use Elliptic\EC;
use kornrunner\Keccak;

/**
 * Derivación BIP32 NO-hardened de direcciones públicas TRON a partir de un
 * XPUB — nunca requiere ni tiene acceso a la clave privada/seed. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 2.
 *
 * Matemáticamente: un hijo público no-hardened es
 *   K_child = K_parent + (IL * G)
 * donde IL sale de HMAC-SHA512(chainCode, serP(K_parent) || index) — pura
 * suma de puntos en secp256k1, jamás se opera sobre un escalar privado.
 * Índices >= 2^31 (hardened) son imposibles de derivar así por diseño de
 * BIP32 — este servicio solo acepta índices no-hardened, consistente con
 * "el servidor nunca tiene la seed".
 *
 * Dirección TRON = base58check(0x41 + últimos 20 bytes de Keccak256(pubkey sin comprimir sin el prefijo 0x04)).
 */
final class Bip32PublicDerivationService
{
    private const HARDENED_OFFSET = 0x80000000;

    public function deriveAddress(string $xpub, int $index): string
    {
        if ($index >= self::HARDENED_OFFSET) {
            throw new PaymentsProviderMisconfiguredException(
                'Derivación hardened solicitada desde un XPUB — imposible sin la clave privada.'
            );
        }

        [$chainCode, $publicKeyBytes] = $this->decodeXpub($xpub);

        $ec = new EC('secp256k1');
        $point = $ec->keyFromPublic(bin2hex($publicKeyBytes), 'hex')->getPublic();

        $data = $publicKeyBytes . pack('N', $index);
        $hmac = hash_hmac('sha512', $data, $chainCode, true);
        $il = substr($hmac, 0, 32);

        $childPoint = $ec->curve->g->mul(bin2hex($il))->add($point);

        $uncompressed = "\x04"
            . hex2bin($childPoint->getX()->toString(16, 64))
            . hex2bin($childPoint->getY()->toString(16, 64));

        return $this->publicKeyToTronAddress($uncompressed);
    }

    /**
     * @return array{0: string, 1: string} [chainCode (32 bytes), compressed public key (33 bytes)]
     */
    private function decodeXpub(string $xpub): array
    {
        $decoded = $this->base58Decode($xpub);

        if (strlen($decoded) !== 82) {
            throw new PaymentsProviderMisconfiguredException('PAYMENTS_TRON_XPUB inválido: longitud decodificada incorrecta.');
        }

        $payload = substr($decoded, 0, 78);
        $checksum = substr($decoded, 78, 4);

        if (substr(hash('sha256', hash('sha256', $payload, true), true), 0, 4) !== $checksum) {
            throw new PaymentsProviderMisconfiguredException('PAYMENTS_TRON_XPUB inválido: checksum no coincide.');
        }

        $chainCode = substr($payload, 13, 32);
        $publicKey = substr($payload, 45, 33);

        return [$chainCode, $publicKey];
    }

    private function publicKeyToTronAddress(string $uncompressedPublicKey): string
    {
        $withoutPrefix = substr($uncompressedPublicKey, 1);
        $hash = Keccak::hash($withoutPrefix, 256, true);
        $addressBytes = "\x41" . substr($hash, -20);

        $checksum = substr(hash('sha256', hash('sha256', $addressBytes, true), true), 0, 4);

        return $this->base58Encode($addressBytes . $checksum);
    }

    private const BASE58_ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    private function base58Encode(string $bytes): string
    {
        $num = gmp_import($bytes);
        $encoded = '';

        while (gmp_cmp($num, 0) > 0) {
            [$num, $remainder] = gmp_div_qr($num, 58);
            $encoded = self::BASE58_ALPHABET[gmp_intval($remainder)] . $encoded;
        }

        foreach (str_split($bytes) as $byte) {
            if ($byte === "\x00") {
                $encoded = '1' . $encoded;
                continue;
            }
            break;
        }

        return $encoded;
    }

    private function base58Decode(string $encoded): string
    {
        $num = gmp_init(0);

        foreach (str_split($encoded) as $char) {
            $position = strpos(self::BASE58_ALPHABET, $char);
            if ($position === false) {
                throw new PaymentsProviderMisconfiguredException('PAYMENTS_TRON_XPUB inválido: carácter fuera del alfabeto Base58.');
            }
            $num = gmp_add(gmp_mul($num, 58), $position);
        }

        $bytes = gmp_export($num);

        $leadingZeros = 0;
        foreach (str_split($encoded) as $char) {
            if ($char !== '1') {
                break;
            }
            $leadingZeros++;
        }

        return str_repeat("\x00", $leadingZeros) . $bytes;
    }
}
