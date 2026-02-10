<?php

namespace App\Services\Games\Bingo\GameRooms;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceContextDTO;
use App\Traits\HashedTableDataTrait;

final class BingoGameRoomsService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly UserPasswordService $userPasswordService,
        private readonly \App\Services\Roles\Admin\Commerce\AdminCommerceContextService $contex,
        private readonly \App\Services\Roles\Admin\Commerce\AdminCommerceCrudService $crud,
        protected readonly \App\Contracts\Roles\Commerce\CommerceRepositoryInterface $commerces,
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        private readonly \App\Contracts\GameRepositoryInterface $games,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones
    ) {}

    /**
     * Resolver contexto para comercios.
     *
     * @return AdminCommerceContextDTO
     */
    public function commerceContext(): AdminCommerceContextDTO {}
}
