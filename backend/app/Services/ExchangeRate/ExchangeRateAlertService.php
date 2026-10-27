<?php

namespace App\Services\ExchangeRate;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\ExchangeRateAlertState;
use App\Models\Roles\Commerce;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Customer;
use App\Models\Roles\Customer\CustomerExchangeRate;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Alertas de tasas de cambio (comando exchange-rates:alerts, cada 15 min).
 *
 *  1. Cambio de tasa: cuando la tasa con la que vende una red se mueve
 *     ≥ changePct() desde el último aviso, se avisa a quienes venden con ella:
 *       - oficial: a las comercializadoras (y su red) y clientes que NO tienen
 *         tasa propia de esa moneda — son quienes venden con la oficial;
 *       - propia de una comercializadora: a su red (agencias y vendedores).
 *     El valor avisado queda en exchange_rate_alert_states; el primer valor
 *     que se ve se guarda sin avisar.
 *  2. Tasa propia desactualizada: sin cambios en staleHours() horas → aviso
 *     a su dueño, como mucho una vez al día.
 *  3. Desvío: la tasa propia se aleja ≥ deviationPct() de la oficial → aviso
 *     a su dueño, como mucho una vez al día.
 */
final class ExchangeRateAlertService
{
    /** Repetición mínima de los avisos 2 y 3 mientras la condición siga. */
    private const REPEAT_HOURS = 24;

    private const URL = '/exchange-rate';

    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    /** @return array{official_changes: int, own_changes: int, stale: int, deviations: int, notified: int} */
    public function run(?Carbon $now = null) : array
    {
        $now ??= now();
        $result = ['official_changes' => 0, 'own_changes' => 0, 'stale' => 0, 'deviations' => 0, 'notified' => 0];

        $usdId = (string) Currency::usdId();
        $official = $this->officialRates()->except($usdId);
        $currencies = Currency::whereIn('id', $official->keys()->all())->get(['id', 'iso_code', 'symbol'])->keyBy(fn ($c) => (string) $c->id);

        $commerceRates = $this->ownRates(CommerceExchangeRate::query(), 'commerce_id')->reject(fn ($r) => (string) $r->currency_id === $usdId);
        $customerRates = $this->ownRates(CustomerExchangeRate::query(), 'customer_id')->reject(fn ($r) => (string) $r->currency_id === $usdId);

        // 1a. Cambios de la oficial.
        foreach ($official as $currencyId => $amount) {
            $change = $this->trackChange('platform', $currencyId, $amount);
            if ($change === null) {
                continue;
            }
            $result['official_changes']++;
            $result['notified'] += $this->notifyOfficialChange($currencies->get($currencyId), $change, $commerceRates, $customerRates);
        }

        // 1b. Cambios de las tasas propias de comercializadoras (aviso a su red).
        foreach ($commerceRates as $rate) {
            $change = $this->trackChange("commerce:{$rate->commerce_id}", (string) $rate->currency_id, (float) $rate->amount);
            if ($change === null) {
                continue;
            }
            $commerce = Commerce::find($rate->commerce_id);
            if (! $commerce || $commerce->status !== 'ACTIVE') {
                continue;
            }
            $result['own_changes']++;
            $currency = $this->currency($currencies, (string) $rate->currency_id);
            $result['notified'] += $this->notifyMany(
                $this->networkUserIds($commerce, includeOwner: false),
                "Cambió la tasa del {$currency->iso_code}",
                "{$commerce->name} actualizó su tasa: 1 USD = {$this->amount($currency, $change['new'])} "
                . "(antes {$this->amount($currency, $change['old'])}, {$this->pct($change['pct'])}). Cobra las próximas ventas con la nueva tasa.",
            );
        }
        // El cliente no tiene red: solo se registra su valor.
        foreach ($customerRates as $rate) {
            $this->trackChange("customer:{$rate->customer_id}", (string) $rate->currency_id, (float) $rate->amount);
        }

        // 2 y 3. Tasas propias desactualizadas o alejadas de la oficial.
        foreach ([['commerce', $commerceRates, 'commerce_id'], ['customer', $customerRates, 'customer_id']] as [$type, $rates, $column]) {
            foreach ($rates as $rate) {
                $owner = $type === 'commerce' ? Commerce::find($rate->{$column}) : Customer::find($rate->{$column});
                if (! $owner?->user_id || $owner->status !== 'ACTIVE') {
                    continue;
                }
                $state = $this->state("{$type}:{$rate->{$column}}", (string) $rate->currency_id, (float) $rate->amount);
                $currency = $this->currency($currencies, (string) $rate->currency_id);

                if ($this->checkStale($state, $rate, $now)) {
                    $result['stale']++;
                    $hours = (int) $rate->updated_at->diffInHours($now);
                    $result['notified'] += $this->notifyMany(
                        [(string) $owner->user_id],
                        "Tu tasa del {$currency->iso_code} lleva {$hours} h sin actualizarse",
                        "Tu tasa propia (1 USD = {$this->amount($currency, (float) $rate->amount)}) es la que usan tus salas. "
                        . 'Revísala o desactívala para usar la oficial.',
                    );
                }

                $officialAmount = $official->get((string) $rate->currency_id);
                if ($officialAmount && $this->checkDeviation($state, (float) $rate->amount, $officialAmount, $now)) {
                    $result['deviations']++;
                    $deviation = ((float) $rate->amount - $officialAmount) / $officialAmount * 100;
                    $result['notified'] += $this->notifyMany(
                        [(string) $owner->user_id],
                        "Tu tasa del {$currency->iso_code} se aleja de la oficial",
                        "Tu tasa propia (1 USD = {$this->amount($currency, (float) $rate->amount)}) está {$this->pct($deviation)} "
                        . "respecto a la oficial ({$this->amount($currency, $officialAmount)}). Revísala para no cobrar de más o de menos.",
                    );
                }
            }
        }

        return $result;
    }

    private function amount(Currency $currency, float $value) : string
    {
        return trim(($currency->symbol ?? '') . ' ' . number_format($value, 2, ',', '.'));
    }

    private function checkDeviation(ExchangeRateAlertState $state, float $own, float $official, Carbon $now) : bool
    {
        if (abs(($own - $official) / $official * 100) < ExchangeRateAlertSettings::deviationPct()) {
            return false;
        }

        $last = $state->deviation_notified_at;
        if ($last && $last->gt($now->copy()->subHours(self::REPEAT_HOURS))) {
            return false;
        }

        $state->update(['deviation_notified_at' => $now]);

        return true;
    }

    private function checkStale(ExchangeRateAlertState $state, $rate, Carbon $now) : bool
    {
        $updatedAt = $rate->updated_at;
        if (! $updatedAt || $updatedAt->gt($now->copy()->subHours(ExchangeRateAlertSettings::staleHours()))) {
            return false;
        }

        // Una vez al día, y de nuevo si la actualizó desde el último aviso.
        $last = $state->stale_notified_at;
        if ($last && $last->gt($updatedAt) && $last->gt($now->copy()->subHours(self::REPEAT_HOURS))) {
            return false;
        }

        $state->update(['stale_notified_at' => $now]);

        return true;
    }

    /** @param Collection<string, Currency> $currencies */
    private function currency(Collection $currencies, string $currencyId) : Currency
    {
        return $currencies->get($currencyId)
            ?? Currency::find($currencyId, ['id', 'iso_code', 'symbol'])
            ?? (new Currency)->forceFill(['iso_code' => '—', 'symbol' => '']);
    }

    /** @return string[] dueño (opcional), agencias y vendedores de la comercializadora */
    private function networkUserIds(Commerce $commerce, bool $includeOwner) : array
    {
        $ids = collect($commerce->networkUserIds())
            ->merge($commerce->subsidiaries()->pluck('user_id')->map(fn ($id) => (string) $id));

        if (! $includeOwner) {
            // Los IDs pueden llegar como enteros o cadenas según la relación.
            // Normalizarlos evita notificar al comercio que actualizó su propia tasa.
            $ids = $ids->reject(fn ($id) => (string) $id === (string) $commerce->user_id);
        }

        return $ids->filter()->unique()->values()->all();
    }

    /** @param string[] $userIds */
    private function notifyMany(array $userIds, string $title, string $message) : int
    {
        $sent = 0;
        foreach ($userIds as $userId) {
            try {
                $this->notifications->create($userId, 'EXCHANGE_RATE', $title, $message, ['url' => self::URL]);
                $sent++;
            } catch (Throwable $e) {
                Log::warning('[ExchangeRateAlert] No se pudo notificar', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    /**
     * Cambio de la oficial: a quienes venden con ella esa moneda (sin tasa propia).
     *
     * @param Collection<int, CommerceExchangeRate> $commerceRates
     * @param Collection<int, CustomerExchangeRate> $customerRates
     */
    private function notifyOfficialChange(?Currency $currency, array $change, Collection $commerceRates, Collection $customerRates) : int
    {
        if (! $currency) {
            return 0;
        }
        $currencyId = (string) $currency->id;

        $withOwnCommerce = $commerceRates->where('currency_id', $currencyId)->pluck('commerce_id')->map(fn ($id) => (string) $id)->all();
        $withOwnCustomer = $customerRates->where('currency_id', $currencyId)->pluck('customer_id')->map(fn ($id) => (string) $id)->all();

        $userIds = collect();

        Commerce::where('status', 'ACTIVE')
            ->whereNotIn('id', $withOwnCommerce)
            ->whereHas('currencies', fn ($q) => $q->where('currencies.id', $currencyId))
            ->get()
            ->each(function(Commerce $commerce) use (&$userIds) {
                $userIds = $userIds->merge($this->networkUserIds($commerce, includeOwner: true));
            });

        $userIds = $userIds->merge(
            Customer::where('status', 'ACTIVE')
                ->where('currency_id', $currencyId)
                ->whereNotIn('id', $withOwnCustomer)
                ->pluck('user_id')
                ->map(fn ($id) => (string) $id),
        );

        return $this->notifyMany(
            $userIds->unique()->values()->all(),
            "Cambió la tasa oficial del {$currency->iso_code}",
            "Nueva tasa oficial: 1 USD = {$this->amount($currency, $change['new'])} "
            . "(antes {$this->amount($currency, $change['old'])}, {$this->pct($change['pct'])}). Las próximas ventas se cobran con ella.",
        );
    }

    /** @return Collection<string, float> tasa oficial ACTIVA más reciente por moneda */
    private function officialRates() : Collection
    {
        return ExchangeRate::active()
            ->latest('updated_at')
            ->get(['currency_id', 'amount', 'updated_at'])
            ->unique('currency_id')
            ->mapWithKeys(fn (ExchangeRate $r) => [(string) $r->currency_id => (float) $r->amount])
            ->filter(fn (float $amount) => $amount > 0);
    }

    /** Tasa propia ACTIVA más reciente por dueño y moneda. */
    private function ownRates($query, string $ownerColumn) : Collection
    {
        return $query->where('status', 'ACTIVE')
            ->where('amount', '>', 0)
            ->orderByDesc('updated_at')
            ->get(['id', $ownerColumn, 'currency_id', 'amount', 'updated_at'])
            ->unique(fn ($r) => $r->{$ownerColumn} . ':' . $r->currency_id)
            ->values();
    }

    private function pct(float $value) : string
    {
        return ($value > 0 ? '+' : '') . number_format($value, 2, ',', '.') . '%';
    }

    private function state(string $ownerKey, string $currencyId, float $amount) : ExchangeRateAlertState
    {
        return ExchangeRateAlertState::firstOrCreate(
            ['owner_key' => $ownerKey, 'currency_id' => $currencyId],
            ['last_amount' => $amount],
        );
    }

    // -------------------------------------------------------------------------

    /**
     * Compara con el último valor avisado. Devuelve el cambio si supera el
     * umbral (y guarda el nuevo valor) o null. La primera vez solo guarda.
     *
     * @return array{old: float, new: float, pct: float}|null
     */
    private function trackChange(string $ownerKey, string $currencyId, float $amount) : ?array
    {
        $state = ExchangeRateAlertState::firstOrNew(['owner_key' => $ownerKey, 'currency_id' => $currencyId]);

        if (! $state->exists || $amount <= 0) {
            $state->last_amount = $amount;
            $state->save();

            return null;
        }

        $old = (float) $state->last_amount;
        $pct = $old > 0 ? ($amount - $old) / $old * 100 : 0.0;

        if (abs($pct) < ExchangeRateAlertSettings::changePct()) {
            return null;
        }

        $state->update(['last_amount' => $amount]);

        return ['old' => $old, 'new' => $amount, 'pct' => $pct];
    }
}
