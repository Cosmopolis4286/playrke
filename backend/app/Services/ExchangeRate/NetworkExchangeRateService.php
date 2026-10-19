<?php

namespace App\Services\ExchangeRate;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Roles\Commerce;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Customer;
use App\Models\Roles\Customer\CustomerExchangeRate;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Models\User;
use App\Services\Roles\RoleUserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tasa de cambio vigente para la red de un usuario (widget de los tableros
 * de comercializadora, agencia, vendedor y cliente).
 *
 * Una venta usa la tasa del comercio dueño de la sala: su tasa propia
 * (commerces_exchange_rates) si la tiene, si no la oficial (exchange_rates)
 * — ver ExchangeRateDomainService y BingoCardGenerateService::saleExchangeRate().
 * Aquí se resuelve esa misma tasa para las salas de la comercializadora
 * del usuario:
 *  - commerce:   la suya;
 *  - subsidiary: la de su comercializadora;
 *  - seller:     la de su comercializadora (Seller::resolveCommerce());
 *  - customer:   la suya (customers_exchange_rates), creador de sus salas;
 *  - roles de plataforma: la oficial.
 */
final class NetworkExchangeRateService
{
    /** Puntos del sparkline (mismo criterio que ExchangeRateSummaryService). */
    private const HISTORY_POINTS = 7;

    private const CACHE_TTL = 90;

    public function __construct(
        private readonly RoleUserService $roleUserService,
        private readonly ExchangeRateDeviationCap $deviationCap,
    ) {}

    /**
     * @return array{
     *   owner: array{type: 'commerce'|'customer'|'platform', name: ?string},
     *   role: ?string,
     *   can_manage: bool,
     *   rates: array<int, array<string, mixed>>
     * }
     */
    public function forUser(User $user): array
    {
        $role  = $this->roleUserService->getActiveUserRolePrefix((int) $user->id);
        $owner = $this->ownerFor($role, (string) $user->id);

        $official = $this->officialRates();
        $own      = $owner ? $this->ownRates($owner) : collect();

        // Solo las monedas con las que vende la red (las habilitadas del dueño
        // de las salas + las que tienen tasa propia); sin dueño, todas.
        $handled     = $owner ? $this->handledCurrencyIds($owner) : null;
        $currencyIds = $official->keys()
            ->when($handled !== null, fn (Collection $ids) => $ids->intersect($handled))
            ->merge($own->keys())
            ->unique()
            ->values();
        $currencies  = Currency::whereIn('id', $currencyIds)->get(['id', 'iso_code', 'symbol', 'name'])->keyBy(fn ($c) => (string) $c->id);

        $rates = $currencyIds
            ->map(fn (string $currencyId) => $this->row($currencyId, $currencies->get($currencyId), $official->get($currencyId), $own->get($currencyId)))
            ->filter()
            // Primero las que tienen tasa propia, luego por código.
            ->sortBy(fn (array $r) => ($r['own'] ? '0' : '1') . $r['currency_code'])
            ->values()
            ->all();

        return [
            'owner'      => $owner
                ? ['type' => $owner instanceof Commerce ? 'commerce' : 'customer', 'name' => $owner->business_name ?? $owner->name]
                : ['type' => 'platform', 'name' => null],
            'role'       => $role,
            // Solo el dueño de las salas fija su tasa propia (ExchangeRateCrudService).
            'can_manage' => in_array($role, ['commerce', 'customer'], true) && $user->checkPermissionTo('exchange.manage'),
            'rates'      => $rates,
        ];
    }

    /** Dueño de la tasa que aplica a las salas del usuario (null = la oficial). */
    private function ownerFor(?string $role, string $userId): Commerce|Customer|null
    {
        return match ($role) {
            'commerce'   => Commerce::where('user_id', $userId)->first(),
            'customer'   => Customer::where('user_id', $userId)->first(),
            'subsidiary' => Subsidiary::where('user_id', $userId)->first()?->commerce,
            'seller'     => Seller::where('user_id', $userId)->first()?->resolveCommerce(),
            default      => null,
        };
    }

    /**
     * Monedas habilitadas del dueño de las salas, o null si no tiene ninguna
     * configurada (en ese caso no se filtra).
     *
     * @return string[]|null
     */
    private function handledCurrencyIds(Commerce|Customer $owner): ?array
    {
        $ids = $owner instanceof Commerce
            ? DB::table('commerces_currencies')->where('commerce_id', $owner->id)->where('status', 'ACTIVE')->pluck('currency_id')
            : collect([$owner->currency_id])->filter();

        $ids = $ids->map(fn ($id) => (string) $id)->values()->all();

        return $ids ?: null;
    }

    private function row(string $currencyId, ?Currency $currency, ?array $official, CommerceExchangeRate|CustomerExchangeRate|null $own): ?array
    {
        if (!$currency || $currencyId === (string) Currency::usdId()) {
            return null;
        }

        $ownAmount      = $own ? (float) $own->amount : null;
        $officialAmount = $official['amount'] ?? null;
        $useOwn         = $ownAmount !== null && $ownAmount > 0;

        return [
            'currency_id'   => $currencyId,
            'currency_code' => $currency->iso_code,
            'currency_name' => $currency->name,
            'symbol'        => $currency->symbol ?? '',
            // Lo que se aplica hoy en las ventas de las salas de la red.
            'effective'     => $useOwn ? $ownAmount : $officialAmount,
            'source'        => $useOwn ? 'own' : 'official',
            'official'      => $official,
            'own'           => $own ? [
                'amount'     => $ownAmount,
                'percentage' => (float) $own->percentage,
                'updated_at' => $own->updated_at?->toIso8601String(),
            ] : null,
            // Cuánto se aleja la tasa propia de la oficial (+ = más cara).
            'deviation_pct' => $deviation = ($useOwn && $officialAmount
                ? round(($ownAmount - $officialAmount) / $officialAmount * 100, 2)
                : null),
            // Mismos umbrales que las alertas (ExchangeRateAlertService).
            'deviation_alert' => $deviation !== null && abs($deviation) >= ExchangeRateAlertSettings::deviationPct(),
            'stale'           => $useOwn && $own->updated_at
                && $own->updated_at->lt(now()->subHours(ExchangeRateAlertSettings::staleHours())),
            // Rango permitido para la tasa propia (tope del admin), o null sin tope.
            'allowed_range'   => $this->deviationCap->range($currencyId),
        ];
    }

    /**
     * Tasa oficial ACTIVA más reciente por moneda, con su historial.
     *
     * @return Collection<string, array<string, mixed>>
     */
    private function officialRates(): Collection
    {
        return collect(Cache::remember('exchange-rate:network:official', self::CACHE_TTL, fn () => ExchangeRate::active()
            ->latest('updated_at')
            ->get(['id', 'currency_id', 'amount', 'percentage', 'source', 'updated_at'])
            ->unique('currency_id')
            ->mapWithKeys(fn (ExchangeRate $rate) => [(string) $rate->currency_id => [
                'amount'     => (float) $rate->amount,
                'percentage' => (float) $rate->percentage,
                'source'     => $rate->source ?? ExchangeRate::SOURCE_USER,
                'updated_at' => $rate->updated_at?->toIso8601String(),
                'history'    => $this->history((string) $rate->currency_id),
            ]])
            ->all()));
    }

    /** @return Collection<string, CommerceExchangeRate|CustomerExchangeRate> tasa propia ACTIVA más reciente por moneda */
    private function ownRates(Commerce|Customer $owner): Collection
    {
        $query = $owner instanceof Commerce
            ? CommerceExchangeRate::where('commerce_id', $owner->id)
            : CustomerExchangeRate::where('customer_id', $owner->id);

        return $query
            ->where('status', 'ACTIVE')
            ->orderByDesc('updated_at')
            ->get(['id', 'currency_id', 'amount', 'percentage', 'updated_at'])
            ->unique('currency_id')
            ->keyBy(fn ($r) => (string) $r->currency_id);
    }

    /** Últimos N valores oficiales (ACTIVE + OLD), del más antiguo al más reciente. */
    private function history(string $currencyId): array
    {
        return ExchangeRate::where('currency_id', $currencyId)
            ->whereIn('status', ['ACTIVE', 'OLD'])
            ->latest('updated_at')
            ->limit(self::HISTORY_POINTS)
            ->get(['amount', 'updated_at'])
            ->sortBy('updated_at')
            ->values()
            ->map(fn (ExchangeRate $rate) => [
                'date'   => $rate->updated_at?->toIso8601String(),
                'amount' => (float) $rate->amount,
            ])
            ->all();
    }
}
