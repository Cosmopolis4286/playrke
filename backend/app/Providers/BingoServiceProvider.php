<?php

namespace App\Providers;

use App\Domains\Games\Bingo\Contracts\Card\CardLot\BingoCardLotRepositoryInterface;
use App\Domains\Games\Bingo\Contracts\Card\CardPrice\BingoCardPriceRepositoryInterface;
use App\Domains\Games\Bingo\Contracts\Pattern\BingoPatternRepositoryInterface;
use App\Domains\Games\Bingo\Contracts\Room\BingoRoomAutomaticRepositoryInterface;
use App\Domains\Games\Bingo\Contracts\Room\BingoRoomRepositoryInterface;
use App\Domains\Games\Bingo\Contracts\Room\Config\BingoRoomConfigRepositoryInterface;
use App\Domains\Games\Bingo\Contracts\Room\User\BingoRoomUserRepositoryInterface;
use App\Domains\Games\Bingo\Contracts\Round\BingoRoundRepositoryInterface;
use App\Domains\Games\Bingo\Contracts\CommissionCalculationInterface;
use App\Domains\Games\Bingo\Contracts\RoomMetricsInterface;
use App\Domains\Games\Bingo\Contracts\RoomUserRegistrationInterface;
use App\Domains\Games\Bingo\Repositories\Card\CardLot\BingoCardLotRepository;
use App\Domains\Games\Bingo\Repositories\Card\CardPrice\BingoCardPriceRepository;
use App\Domains\Games\Bingo\Repositories\Pattern\BingoPatternRepository;
use App\Domains\Games\Bingo\Repositories\Room\BingoRoomAutomaticRepository;
use App\Domains\Games\Bingo\Repositories\Room\BingoRoomRepository;
use App\Domains\Games\Bingo\Repositories\Room\BingoRoomUserRepository;
use App\Domains\Games\Bingo\Repositories\Room\Config\BingoRoomConfigRepository;
use App\Domains\Games\Bingo\Repositories\Round\BingoRoundRepository;
use App\Domains\Games\Bingo\Services\Payment\CommissionCalculationService;
use App\Domains\Games\Bingo\Services\Room\Metrics\RoomMetricsService;
use App\Domains\Games\Bingo\Services\Room\User\RoomUserRegistrationService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider del módulo Bingo.
 *
 * Registra explícitamente los bindings de repositorios que antes resolvía
 * el auto-scanner de RepositoryServiceProvider (escaneaba app/Repositories/**).
 * Al mover los repositorios de Bingo a app/Domains/Games/Bingo/Repositories,
 * ese scanner deja de encontrarlos — este Provider reemplaza ese mecanismo
 * implícito con bindings explícitos, siguiendo el mismo patrón que
 * TriviaServiceProvider.
 *
 * Las interfaces *RepositoryContract (BingoCard, BingoCardPayment,
 * BingoDraw, BingoDrawCode, BingoImage, BingoCreator, BingoCreatorSale,
 * BingoCardPrize, BingoCardPrizePayment, BingoRoomWinner) no tienen
 * binding aquí: no tienen ningún caller real en el proyecto (deuda técnica
 * documentada, no se resuelve en esta fase).
 */
class BingoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(BingoCardLotRepositoryInterface::class, BingoCardLotRepository::class);
        $this->app->singleton(BingoCardPriceRepositoryInterface::class, BingoCardPriceRepository::class);
        $this->app->singleton(BingoPatternRepositoryInterface::class, BingoPatternRepository::class);
        $this->app->singleton(BingoRoomAutomaticRepositoryInterface::class, BingoRoomAutomaticRepository::class);
        $this->app->singleton(BingoRoomRepositoryInterface::class, BingoRoomRepository::class);
        $this->app->singleton(BingoRoomConfigRepositoryInterface::class, BingoRoomConfigRepository::class);
        $this->app->singleton(BingoRoomUserRepositoryInterface::class, BingoRoomUserRepository::class);
        $this->app->singleton(BingoRoundRepositoryInterface::class, BingoRoundRepository::class);

        $this->app->singleton(CommissionCalculationInterface::class, CommissionCalculationService::class);
        $this->app->singleton(RoomMetricsInterface::class, RoomMetricsService::class);
        $this->app->singleton(RoomUserRegistrationInterface::class, RoomUserRegistrationService::class);
    }
}
