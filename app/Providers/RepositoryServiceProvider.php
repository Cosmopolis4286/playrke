<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Contratos
use App\Repositories\Contracts\BaseRepositoryContract;
use App\Repositories\Contracts\Users\UserRepositoryContract;
use App\Repositories\Contracts\Games\Bingo\BingoRoomRepositoryContract;
use App\Repositories\Contracts\Commands\CommandLogRepositoryContract;
use App\Repositories\Contracts\Banks\BankVenezuelaRepositoryContract;
use App\Repositories\Contracts\Countries\CountryRepositoryContract;
use App\Repositories\Contracts\Currencies\CurrencyRepositoryContract;
use App\Repositories\Contracts\Games\GameRepositoryContract;
use App\Repositories\Contracts\Games\Utils\GameConfigRepositoryContract;
use App\Repositories\Contracts\Wallets\Crypto\BinanceAccountsRepositoryContract;
use App\Repositories\Contracts\Games\Bingo\Cards\BingoCardRepositoryContract;
use App\Repositories\Contracts\Games\Bingo\Cards\BingoCardLotRepositoryContract;
use App\Repositories\Contracts\Games\Bingo\Cards\BingoCardPaymentRepositoryContract;
use App\Repositories\Contracts\Wallets\WalletRepositoryContract;
use App\Repositories\Contracts\Games\Bingo\Cards\CardPrice\BingoCardPriceRepositoryContract;
use App\Repositories\Contracts\Games\Bingo\Cards\CardPrice\BingoCardPriceDraftRepositoryContract;
use App\Repositories\Contracts\Settings\SettingRepositoryContract;
use App\Repositories\Contracts\Rewards\RewardRepositoryContract;
use App\Repositories\Contracts\Games\Bingo\Cards\CardPrize\BingoCardPrizeRepositoryContract;
use App\Repositories\Games\Bingo\Cards\CardPrize\Contracts\BingoCardPrizePaymentRepositoryContract;
use App\Repositories\Games\Bingo\Creators\Contracts\BingoCreatorRepositoryContract;
use App\Repositories\Games\Bingo\Creators\Contracts\BingoCreatorSaleRepositoryContract;
use App\Repositories\Games\Bingo\Draws\Contracts\BingoDrawRepositoryContract;
use App\Repositories\Games\Bingo\Draws\Contracts\BingoDrawCodeRepositoryContract;
use App\Repositories\Games\Bingo\Images\Contracts\BingoImageRepositoryContract;
use App\Repositories\Games\Bingo\Cards\Patterns\Contracts\BingoPatternRepositoryContract;

// Implementaciones
use App\Repositories\BaseRepository;
use App\Repositories\Users\UserRepository;
use App\Repositories\Games\Bingo\BingoRoomRepository;
use App\Repositories\Commands\CommandLogRepository;
use App\Repositories\Banks\BankVenezuelaRepository;
use App\Repositories\Countries\CountryRepository;
use App\Repositories\Currencies\CurrencyRepository;
use App\Repositories\Games\GameRepository;
use App\Repositories\Games\Utils\GameConfigRepository;
use App\Repositories\Wallets\Crypto\BinanceAccountsRepository;
use App\Repositories\Games\Bingo\Cards\BingoCardRepository;
use App\Repositories\Games\Bingo\Cards\BingoCardLotRepository;
use App\Repositories\Games\Bingo\Cards\BingoCardPaymentRepository;
use App\Repositories\Wallets\WalletRepository;
use App\Repositories\Games\Bingo\Cards\CardPrice\BingoCardPriceRepository;
use App\Repositories\Games\Bingo\Cards\CardPrice\BingoCardPriceDraftRepository;
use App\Repositories\Settings\SettingRepository;
use App\Repositories\Rewards\RewardRepository;
use App\Repositories\Games\Bingo\Cards\CardPrize\BingoCardPrizeRepository;
use App\Repositories\Games\Bingo\Cards\CardPrize\BingoCardPrizePaymentRepository;
use App\Repositories\Games\Bingo\Creators\BingoCreatorRepository;
use App\Repositories\Games\Bingo\Creators\BingoCreatorSaleRepository;
use App\Repositories\Games\Bingo\Draws\BingoDrawRepository;
use App\Repositories\Games\Bingo\Draws\BingoDrawCodeRepository;
use App\Repositories\Games\Bingo\Images\BingoImageRepository;
use App\Repositories\Games\Bingo\Cards\Patterns\BingoPatternRepository;

/**
 * Service Provider para registrar los repositorios
 * Facilita la inyección de dependencias usando los contratos
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios
     *
     * @return void
     */
    public function register(): void
    {
        // Registrar repositorios de usuarios
        $this->app->bind(UserRepositoryContract::class, UserRepository::class);

        // Registrar repositorios de juegos - Bingo
        $this->app->bind(BingoRoomRepositoryContract::class, BingoRoomRepository::class);

        // Registrar repositorios de bancos
        $this->app->bind(BankVenezuelaRepositoryContract::class, BankVenezuelaRepository::class);

        // Registrar repositorios de comandos
        $this->app->bind(CommandLogRepositoryContract::class, CommandLogRepository::class);

        // Registrar repositorios de países
        $this->app->bind(CountryRepositoryContract::class, CountryRepository::class);

        // Registrar repositorios de monedas
        $this->app->bind(CurrencyRepositoryContract::class, CurrencyRepository::class);

        // Registrar repositorios de juegos
        $this->app->bind(GameRepositoryContract::class, GameRepository::class);
        $this->app->bind(GameConfigRepositoryContract::class, GameConfigRepository::class);

        // Registrar repositorios de wallets
        $this->app->bind(BinanceAccountsRepositoryContract::class, BinanceAccountsRepository::class);

        // Registrar repositorios de cartones de bingo
        $this->app->bind(BingoCardRepositoryContract::class, BingoCardRepository::class);
        $this->app->bind(BingoCardLotRepositoryContract::class, BingoCardLotRepository::class);
        $this->app->bind(BingoCardPaymentRepositoryContract::class, BingoCardPaymentRepository::class);

        // Registrar repositorios de wallets
        $this->app->bind(WalletRepositoryContract::class, WalletRepository::class);

        // Registrar repositorios de precios de cartones
        $this->app->bind(BingoCardPriceRepositoryContract::class, BingoCardPriceRepository::class);
        $this->app->bind(BingoCardPriceDraftRepositoryContract::class, BingoCardPriceDraftRepository::class);

        // Registrar repositorios de configuraciones
        $this->app->bind(SettingRepositoryContract::class, SettingRepository::class);

        // Registrar repositorios de recompensas
        $this->app->bind(RewardRepositoryContract::class, RewardRepository::class);

        // Registrar repositorios de premios de cartones de bingo
        $this->app->bind(BingoCardPrizeRepositoryContract::class, BingoCardPrizeRepository::class);
        $this->app->bind(BingoCardPrizePaymentRepositoryContract::class, BingoCardPrizePaymentRepository::class);

        // Registrar repositorios de creadores de bingo
        $this->app->bind(BingoCreatorRepositoryContract::class, BingoCreatorRepository::class);
        $this->app->bind(BingoCreatorSaleRepositoryContract::class, BingoCreatorSaleRepository::class);

        // Registrar repositorios de sorteos de bingo
        $this->app->bind(BingoDrawRepositoryContract::class, BingoDrawRepository::class);
        $this->app->bind(BingoDrawCodeRepositoryContract::class, BingoDrawCodeRepository::class);

        // Registrar repositorios de imágenes de bingo
        $this->app->bind(BingoImageRepositoryContract::class, BingoImageRepository::class);

        // Registrar repositorios de patrones de bingo
        $this->app->bind(BingoPatternRepositoryContract::class, BingoPatternRepository::class);

        // Registrar otros repositorios según se vayan creando
        // $this->app->bind(WalletRepositoryContract::class, WalletRepository::class);
    }

    /**
     * Inicializar servicios después del registro
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Obtener servicios proporcionados por este provider
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            UserRepositoryContract::class,
            BingoRoomRepositoryContract::class,
            BankVenezuelaRepositoryContract::class,
            CommandLogRepositoryContract::class,
            CountryRepositoryContract::class,
            CurrencyRepositoryContract::class,
            GameRepositoryContract::class,
            GameConfigRepositoryContract::class,
            BinanceAccountsRepositoryContract::class,
            BingoCardRepositoryContract::class,
            BingoCardLotRepositoryContract::class,
            BingoCardPaymentRepositoryContract::class,
            WalletRepositoryContract::class,
            BingoCardPriceRepositoryContract::class,
            BingoCardPriceDraftRepositoryContract::class,
            SettingRepositoryContract::class,
            RewardRepositoryContract::class,
            BingoCardPrizeRepositoryContract::class,
            BingoCardPrizePaymentRepositoryContract::class,
            BingoCreatorRepositoryContract::class,
            BingoCreatorSaleRepositoryContract::class,
            BingoDrawRepositoryContract::class,
            BingoDrawCodeRepositoryContract::class,
            BingoImageRepositoryContract::class,
            BingoPatternRepositoryContract::class,
        ];
    }
}