<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Contratos
use App\Repositories\Contracts\BaseRepositoryContract;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use App\Repositories\Games\Bingo\Contracts\BingoRoomRepositoryContract;
use App\Repositories\Commands\Contracts\CommandLogRepositoryContract;
use App\Repositories\Banks\Contracts\BankBrazilRepositoryContract;
use App\Repositories\Banks\Contracts\BankVenezuelaRepositoryContract;
use App\Repositories\Countries\Contracts\CountryRepositoryContract;
use App\Repositories\Currencies\Contracts\CurrencyRepositoryContract;
use App\Repositories\Games\Contracts\GameRepositoryContract;
use App\Repositories\Games\Utils\Contracts\GameConfigRepositoryContract;
use App\Repositories\Wallets\Crypto\Contracts\BinanceAccountsRepositoryContract;
use App\Repositories\Games\Bingo\Cards\Contracts\BingoCardRepositoryContract;
use App\Repositories\Games\Bingo\Cards\Contracts\BingoCardLotRepositoryContract;
use App\Repositories\Games\Bingo\Cards\Contracts\BingoCardPaymentRepositoryContract;
use App\Repositories\Wallets\Contracts\WalletRepositoryContract;
use App\Repositories\Games\Bingo\Cards\CardPrice\Contracts\BingoCardPriceRepositoryContract;
use App\Repositories\Games\Bingo\Cards\CardPrice\Contracts\BingoCardPriceDraftRepositoryContract;
use App\Repositories\Settings\Contracts\SettingRepositoryContract;
use App\Repositories\Rewards\Contracts\RewardRepositoryContract;
use App\Repositories\Games\Bingo\Cards\CardPrize\Contracts\BingoCardPrizeRepositoryContract;
use App\Repositories\Games\Bingo\Cards\CardPrize\Contracts\BingoCardPrizePaymentRepositoryContract;
use App\Repositories\Games\Bingo\Creators\Contracts\BingoCreatorRepositoryContract;
use App\Repositories\Games\Bingo\Creators\Contracts\BingoCreatorSaleRepositoryContract;
use App\Repositories\Games\Bingo\Draws\Contracts\BingoDrawRepositoryContract;
use App\Repositories\Games\Bingo\Draws\Contracts\BingoDrawCodeRepositoryContract;
use App\Repositories\Games\Bingo\Images\Contracts\BingoImageRepositoryContract;
use App\Repositories\Games\Bingo\Cards\Patterns\Contracts\BingoPatternRepositoryContract;
use App\Repositories\Games\Bingo\Rooms\Contracts\BingoRoomConfigRepositoryContract;
use App\Repositories\Games\Bingo\Rooms\Contracts\BingoRoomUserRepositoryContract;
use App\Repositories\Games\Bingo\Rooms\Contracts\BingoRoomUserWinnerRepositoryContract;
use App\Repositories\Games\Bingo\Rounds\Contracts\BingoRoundRepositoryContract;
use App\Repositories\Games\Bingo\Rounds\Contracts\BingoRoundDraftRepositoryContract;
use App\Repositories\Games\Shared\GameRoom\Contracts\CurrencyGameRoomRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceCountriesCurrencysRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceExchangeRateRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceGameRepositoryContract;
use App\Repositories\Users\Roles\Company\Contracts\CompanyRepositoryContract;
use App\Repositories\Users\Roles\Customer\Contracts\CustomerRepositoryContract;
use App\Repositories\Users\Roles\Customer\Contracts\CustomerExchangeRateRepositoryContract;
use App\Repositories\Users\Roles\Customer\Contracts\CustomerGameRepositoryContract;
use App\Repositories\Users\Roles\Customer\Contracts\CustomerUserRepositoryContract;
use App\Repositories\Users\Shared\Contracts\DocumentTypeRepositoryContract;
use App\Repositories\Payments\ExchangeRate\Contracts\ExchangeRateRepositoryContract;
use App\Repositories\Users\Roles\Player\External\Contracts\ExternalPlayerRepositoryContract;
use App\Repositories\Games\Shared\Utils\Contracts\GameExchangeRateRepositoryContract;
use App\Repositories\Games\Shared\GameMode\Contracts\GameModeRepositoryContract;

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
use App\Repositories\Games\Bingo\Rooms\BingoRoomConfigRepository;
use App\Repositories\Games\Bingo\Rooms\BingoRoomUserRepository;
use App\Repositories\Games\Bingo\Rooms\BingoRoomUserWinnerRepository;
use App\Repositories\Games\Bingo\Rounds\BingoRoundRepository;
use App\Repositories\Games\Bingo\Rounds\BingoRoundDraftRepository;
use App\Repositories\Games\Shared\GameRoom\CurrencyGameRoomRepository;
use App\Repositories\Users\Roles\Commerce\CommerceRepository;
use App\Repositories\Users\Roles\Commerce\CommerceCountriesCurrencysRepository;
use App\Repositories\Users\Roles\Commerce\CommerceExchangeRateRepository;
use App\Repositories\Users\Roles\Commerce\CommerceGameRepository;
use App\Repositories\Users\Roles\Company\CompanyRepository;
use App\Repositories\Users\Roles\Customer\CustomerRepository;
use App\Repositories\Users\Roles\Customer\CustomerExchangeRateRepository;
use App\Repositories\Users\Roles\Customer\CustomerGameRepository;
use App\Repositories\Users\Roles\Customer\CustomerUserRepository;
use App\Repositories\Users\Shared\DocumentTypeRepository;
use App\Repositories\Payments\ExchangeRate\ExchangeRateRepository;
use App\Repositories\Users\Roles\Player\External\ExternalPlayerRepository;
use App\Repositories\Games\Shared\Utils\GameExchangeRateRepository;
use App\Repositories\Games\Shared\GameMode\GameModeRepository;

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

        // Registrar repositorios de configuraciones de bingo
        $this->app->bind(BingoRoomConfigRepositoryContract::class, BingoRoomConfigRepository::class);

        // Registrar repositorios de usuarios de salas de bingo
        $this->app->bind(BingoRoomUserRepositoryContract::class, BingoRoomUserRepository::class);
        $this->app->bind(BingoRoomUserWinnerRepositoryContract::class, BingoRoomUserWinnerRepository::class);

        // Registrar repositorios de rondas de bingo
        $this->app->bind(BingoRoundRepositoryContract::class, BingoRoundRepository::class);
        $this->app->bind(BingoRoundDraftRepositoryContract::class, BingoRoundDraftRepository::class);

        // Registrar repositorios de salas de juego
        $this->app->bind(CurrencyGameRoomRepositoryContract::class, CurrencyGameRoomRepository::class);

        // Registrar repositorios de comercios
        $this->app->bind(CommerceRepositoryContract::class, CommerceRepository::class);
        $this->app->bind(CommerceCountriesCurrencysRepositoryContract::class, CommerceCountriesCurrencysRepository::class);
        $this->app->bind(CommerceExchangeRateRepositoryContract::class, CommerceExchangeRateRepository::class);
        $this->app->bind(CommerceGameRepositoryContract::class, CommerceGameRepository::class);

        // Registrar repositorios de empresas
        $this->app->bind(CompanyRepositoryContract::class, CompanyRepository::class);

        // Registrar repositorios de clientes
        $this->app->bind(CustomerRepositoryContract::class, CustomerRepository::class);
        $this->app->bind(CustomerExchangeRateRepositoryContract::class, CustomerExchangeRateRepository::class);
        $this->app->bind(CustomerGameRepositoryContract::class, CustomerGameRepository::class);
        $this->app->bind(CustomerUserRepositoryContract::class, CustomerUserRepository::class);

        // Registrar repositorios de tipos de documento
        $this->app->bind(DocumentTypeRepositoryContract::class, DocumentTypeRepository::class);

        // Registrar repositorios de tipos de cambio
        $this->app->bind(ExchangeRateRepositoryContract::class, ExchangeRateRepository::class);

        // Registrar repositorios de jugadores externos
        $this->app->bind(ExternalPlayerRepositoryContract::class, ExternalPlayerRepository::class);

        // Registrar repositorios de tipos de cambio de juegos
        $this->app->bind(GameExchangeRateRepositoryContract::class, GameExchangeRateRepository::class);

        // Registrar repositorios de modos de juego
        $this->app->bind(GameModeRepositoryContract::class, GameModeRepository::class);

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