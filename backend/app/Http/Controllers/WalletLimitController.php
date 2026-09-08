<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Domains\Wallet\Models\WalletLimit;
use App\Domains\Wallet\Services\WalletLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletLimitController extends Controller
{
    private const VALIDATION_RULES = [
        'currency_id'         => 'nullable|string|exists:currencies,id',
        'kyc_level'           => 'required|integer|min:0|max:2',
        'min_deposit'         => 'required|numeric|min:0',
        'max_deposit'         => 'required|numeric|gt:min_deposit',
        'min_per_transaction' => 'required|numeric|min:0',
        'max_per_transaction' => 'required|numeric|gt:min_per_transaction',
        'max_daily'           => 'required|numeric|gte:max_per_transaction',
        'max_weekly'          => 'required|numeric|gte:max_daily',
        'max_monthly'         => 'required|numeric|gte:max_weekly',
        'is_active'           => 'required|boolean',
    ];

    /**
     * Display a listing of the wallet limits.
     */
    public function index(Request $request): Response
    {
        $walletLimits = WalletLimit::with('currency:id,iso_code,symbol,name,decimal_places')
            ->orderBy('currency_id')
            ->orderBy('kyc_level')
            ->get();

        $currencies = Currency::query()
            ->active()
            ->orderBy('iso_code')
            ->get(['id', 'iso_code', 'symbol', 'name', 'decimal_places']);

        return Inertia::render('WalletLimits/Index', [
            'walletLimits' => $walletLimits,
            'currencies'   => $currencies,
        ]);
    }

    /**
     * Store a newly created wallet limit.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(self::VALIDATION_RULES);

        // No se usa una regla `unique` de Laravel porque el par (currency_id
        // NULL, kyc_level) no se puede expresar bien con la sintaxis de unique
        // cuando currency_id es nullable; se valida explícitamente aquí.
        $exists = WalletLimit::where('kyc_level', $validated['kyc_level'])
            ->where(fn ($q) => empty($validated['currency_id'])
                ? $q->whereNull('currency_id')
                : $q->where('currency_id', $validated['currency_id']))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una configuración de límites para esta combinación de moneda y nivel KYC.',
            ], 422);
        }

        $walletLimit = WalletLimit::create($validated);
        WalletLimit::forgetCatalogCache();

        return response()->json([
            'success' => true,
            'message' => 'Límites de billetera creados exitosamente',
            'data'    => $walletLimit->load('currency:id,iso_code,symbol,name,decimal_places'),
        ]);
    }

    /**
     * Recomienda los 8 campos de límites para (moneda, nivel KYC), escalando
     * un baseline en USD por la tasa de cambio ACTIVA de esa moneda — para
     * que el admin no tenga que calcular a mano el equivalente de "$100" en
     * VES/COP/BRL cada vez que cambia la tasa. currency_id vacío/ausente =
     * moneda ancla (USDT/USD, tasa 1:1).
     */
    public function recommend(Request $request, WalletLimitService $service): JsonResponse
    {
        $validated = $request->validate([
            'currency_id' => 'nullable|string|exists:currencies,id',
            'kyc_level'   => 'required|integer|min:0|max:2',
        ]);

        $currencyId = $validated['currency_id'] ?? (string) Currency::usdId();

        try {
            $recommended = $service->recommend($currencyId, (int) $validated['kyc_level']);
        } catch (\App\Exceptions\Domain\ExchangeRate\ExchangeRateException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una tasa de cambio activa registrada para esta moneda. Configúrala en Tasas de Cambio antes de recomendar límites.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $recommended,
        ]);
    }

    /**
     * Consumo agregado real (retiros del día/semana/mes) de la plataforma
     * por cada combinación (moneda, nivel KYC) configurada, para que el admin
     * pueda calibrar si un límite está muy ajustado o muy holgado frente al
     * uso real. El fallback global (currency_id NULL) agrega TODAS las
     * monedas que no tengan su propio límite específico — como esas monedas
     * pueden tener escalas muy distintas (ej. USDT vs VES), el total sumado
     * NUNCA se muestra solo: siempre viene acompañado de `by_currency` con el
     * desglose real, para que el frontend no mezcle unidades distintas en un
     * mismo número.
     */
    public function usageSummary(WalletLimitService $service): JsonResponse
    {
        $limits = WalletLimit::with('currency:id,iso_code,symbol,name,decimal_places')->get();
        $rawSummary = $service->usageSummary();

        $currenciesById = Currency::query()
            ->get(['id', 'iso_code', 'symbol', 'name', 'decimal_places'])
            ->keyBy(fn (Currency $c) => (string) $c->id);

        // currency_id específicos que sí tienen su propio WalletLimit — el
        // resto cae en el fallback global y no debe contarse dos veces.
        $specificCurrencyIds = $limits->whereNotNull('currency_id')->pluck('currency_id')->all();

        $result = [];

        foreach ($limits as $limit) {
            $usage = ['daily' => 0.0, 'weekly' => 0.0, 'monthly' => 0.0, 'transaction_count' => 0];
            $byCurrency = [];

            if ($limit->currency_id !== null) {
                foreach ($rawSummary as $key => $entry) {
                    [$currencyId, $kycLevel] = explode(':', $key, 2);
                    if ($currencyId === (string) $limit->currency_id && (int) $kycLevel === (int) $limit->kyc_level) {
                        $usage = $entry;
                        break;
                    }
                }
            } else {
                // Fallback global: agrega todo lo que NO tenga límite específico
                // propio, pero conservando el desglose por moneda (by_currency)
                // — varias monedas comparten este mismo tope numérico, así que
                // sumarlas en una sola cifra sería engañoso (unidades distintas).
                foreach ($rawSummary as $key => $entry) {
                    [$currencyId, $kycLevel] = explode(':', $key, 2);
                    if ((int) $kycLevel !== (int) $limit->kyc_level) {
                        continue;
                    }
                    if (in_array($currencyId, $specificCurrencyIds, true)) {
                        continue;
                    }

                    $usage['daily'] += $entry['daily'];
                    $usage['weekly'] += $entry['weekly'];
                    $usage['monthly'] += $entry['monthly'];
                    $usage['transaction_count'] += $entry['transaction_count'];

                    $currency = $currenciesById->get($currencyId);
                    $byCurrency[] = [
                        'currency_id'    => $currencyId,
                        'iso_code'       => $currency?->iso_code ?? '???',
                        'symbol'         => $currency?->symbol ?? '',
                        'decimal_places' => $currency?->decimal_places ?? 2,
                        'daily'          => $entry['daily'],
                        'weekly'         => $entry['weekly'],
                        'monthly'        => $entry['monthly'],
                        'transaction_count' => $entry['transaction_count'],
                    ];
                }
            }

            $result[(string) $limit->id] = [
                'is_shared_across_currencies' => $limit->currency_id === null,
                'daily'    => ['used' => $usage['daily'],   'max' => (float) $limit->max_daily],
                'weekly'   => ['used' => $usage['weekly'],  'max' => (float) $limit->max_weekly],
                'monthly'  => ['used' => $usage['monthly'], 'max' => (float) $limit->max_monthly],
                'transaction_count' => $usage['transaction_count'],
                'by_currency' => $byCurrency,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * Display the specified wallet limit.
     */
    public function show(string $id): JsonResponse
    {
        $walletLimit = WalletLimit::with('currency:id,iso_code,symbol,name,decimal_places')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $walletLimit,
        ]);
    }

    /**
     * Update the specified wallet limit.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $walletLimit = WalletLimit::findOrFail($id);

        $validated = $request->validate(self::VALIDATION_RULES);

        $exists = WalletLimit::where('id', '!=', $id)
            ->where('kyc_level', $validated['kyc_level'])
            ->where(fn ($q) => empty($validated['currency_id'])
                ? $q->whereNull('currency_id')
                : $q->where('currency_id', $validated['currency_id']))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una configuración de límites para esta combinación de moneda y nivel KYC.',
            ], 422);
        }

        $walletLimit->update($validated);
        WalletLimit::forgetCatalogCache();

        return response()->json([
            'success' => true,
            'message' => 'Límites de billetera actualizados exitosamente',
            'data'    => $walletLimit->fresh()->load('currency:id,iso_code,symbol,name,decimal_places'),
        ]);
    }

    /**
     * Delete the specified wallet limit.
     */
    public function delete(string $id): JsonResponse
    {
        $walletLimit = WalletLimit::findOrFail($id);

        if ($walletLimit->currency_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el límite global de fallback (moneda = Todas). Desactívalo si no quieres aplicarlo.',
            ], 422);
        }

        $walletLimit->delete();
        WalletLimit::forgetCatalogCache();

        return response()->json([
            'success' => true,
            'message' => 'Límites de billetera eliminados exitosamente',
        ]);
    }

    /**
     * Toggle wallet limit active status.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $walletLimit = WalletLimit::findOrFail($id);
        $walletLimit->update(['is_active' => ! $walletLimit->is_active]);
        WalletLimit::forgetCatalogCache();

        return response()->json([
            'success' => true,
            'message' => 'Estado de los límites actualizado exitosamente',
            'data'    => $walletLimit->fresh()->load('currency:id,iso_code,symbol,name,decimal_places'),
        ]);
    }
}
