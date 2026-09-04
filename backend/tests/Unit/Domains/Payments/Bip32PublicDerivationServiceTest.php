<?php

namespace Tests\Unit\Domains\Payments;

use App\Domains\Payments\Services\Bip32PublicDerivationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Vector de prueba generado de forma independiente (no con este código) vía
 * las librerías JS establecidas bip32 + tiny-secp256k1 + js-sha3, a partir
 * del mnemonic BIP39 público de prueba "abandon abandon ... about", ruta
 * m/44'/195'/0' (195 = SLIP-44 TRON). Nunca usar este XPUB en producción —
 * su seed es pública y conocida. Ver scratch de generación en la sesión de
 * diseño de PAYMENTS_USDT_TRC20_DESIGN.md.
 */
class Bip32PublicDerivationServiceTest extends TestCase
{
    private const TEST_XPUB = 'xpub6D1AabNHCupeiLM65ZR9UStMhJ1vCpyV4XbZdyhMZBiJXALQtmn9p42VTQckoHVn8WNqS7dqnJokZHAHcHGoaQgmv8D45oNUKx6DZMNZBCd';

    #[DataProvider('knownAddresses')]
    public function test_derives_the_same_address_as_an_independent_js_implementation(int $index, string $expectedAddress): void
    {
        $service = new Bip32PublicDerivationService();

        $address = $service->deriveAddress(self::TEST_XPUB, $index);

        $this->assertSame($expectedAddress, $address);
    }

    public static function knownAddresses(): array
    {
        return [
            'index 0' => [0, 'TEHBaMgYFRGcbYJwEMtxSwAhY57D2RL5x6'],
            'index 1' => [1, 'TTtGPsEEvLvCEMETvvU1S131Vxzn4JP1v3'],
            'index 5' => [5, 'THshRecBx676ZsRyP44G1WJVqaz4rcY4wC'],
        ];
    }

    public function test_two_different_indexes_never_produce_the_same_address(): void
    {
        $service = new Bip32PublicDerivationService();

        $addresses = array_map(
            fn (int $i) => $service->deriveAddress(self::TEST_XPUB, $i),
            range(0, 9),
        );

        $this->assertCount(10, array_unique($addresses));
    }

    public function test_rejects_hardened_index(): void
    {
        $service = new Bip32PublicDerivationService();

        $this->expectException(\App\Domains\Payments\Exceptions\PaymentsProviderMisconfiguredException::class);
        $service->deriveAddress(self::TEST_XPUB, 0x80000000);
    }
}
