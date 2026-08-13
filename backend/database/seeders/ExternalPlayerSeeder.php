<?php

namespace Database\Seeders;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\UserPurchaseCode;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Jugadores externos demo (compradores de cartones de Bingo sin cuenta en la
 * plataforma, ver App\Services\Roles\Player\External\ExternalPlayerLinkService) — datos de prueba para el
 * panel admin en /admin/external-players.
 *
 * Depende de: RoleSeeder, TestUserSeeder, DemoRoleDataSeeder (commerce/seller
 * demo), CountrySeeder, CurrencySeeder, GameSeeder.
 */
class ExternalPlayerSeeder extends Seeder
{
    public function __construct(
        private readonly ExternalPlayerRepositoryInterface $externalPlayerRepository,
    ) {}

    public function run(): void
    {
        $venezuela = Country::where('iso_code_2', 'VE')->first();
        $ves       = Currency::where('iso_code', 'VES')->first();
        $game      = Game::first();
        $commerceUser = User::where('email', 'commerce@playrke.com')->first();
        $sellerUser   = User::where('email', 'seller@playrke.com')->first();

        if (! $venezuela || ! $ves || ! $game || ! $commerceUser || ! $sellerUser) {
            $this->command->warn('⚠ ExternalPlayerSeeder: faltan dependencias (Country VE, Currency VES, Game, commerce/seller demo). Ejecute los seeders previos.');
            return;
        }

        $demoPlayers = [
            ['first_name' => 'Rosa',    'last_name' => 'Gómez',    'phone' => '+58 412 5551001', 'status' => 'ACTIVE'],
            ['first_name' => 'Carlos',  'last_name' => 'Pérez',    'phone' => '+58 424 5551002', 'status' => 'ACTIVE'],
            ['first_name' => 'Yusleidy','last_name' => 'Rondón',   'phone' => '+58 414 5551003', 'status' => 'INACTIVE'],
            ['first_name' => 'Miguel',  'last_name' => 'Torres',   'phone' => '+58 416 5551004', 'status' => 'BLOCKED'],
        ];

        foreach ($demoPlayers as $data) {
            if (ExternalPlayer::where('phone', $data['phone'])->where('game_id', $game->id)->exists()) {
                continue;
            }

            $purchaseCode = UserPurchaseCode::create([
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'country_id'      => $venezuela->id,
                'phone'           => $data['phone'],
                'purchase_code'   => strtoupper(Str::random(8)),
                'status'          => 'ACTIVE',
                'expiration_date' => now()->addDays(7),
            ]);

            ExternalPlayer::create([
                'game_id'          => $game->id,
                'room_id'          => (string) $game->id,
                'creator_user_id'  => $commerceUser->id,
                'seller_user_id'   => $sellerUser->id,
                'room_user_id'     => (string) Str::random(12),
                'link'             => $this->externalPlayerRepository->generateUniqueLink(),
                'expiration_date'  => now()->addDays(7),
                'country_id'       => $venezuela->id,
                'currency_id'      => $ves->id,
                'first_name'       => $data['first_name'],
                'last_name'        => $data['last_name'],
                'identification'   => null,
                'phone'            => $data['phone'],
                'purchase_code_id' => $purchaseCode->id,
                'status'           => $data['status'],
            ]);
        }

        $this->command->info('✔ ExternalPlayerSeeder: ' . count($demoPlayers) . ' jugadores externos demo procesados.');
    }
}
