<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\WalletLimit;
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
        $walletLimits = WalletLimit::with('currency:id,iso_code,symbol,name')
            ->orderBy('currency_id')
            ->orderBy('kyc_level')
            ->get();

        $currencies = Currency::query()
            ->active()
            ->orderBy('iso_code')
            ->get(['id', 'iso_code', 'symbol', 'name']);

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
            'data'    => $walletLimit->load('currency:id,iso_code,symbol,name'),
        ]);
    }

    /**
     * Display the specified wallet limit.
     */
    public function show(string $id): JsonResponse
    {
        $walletLimit = WalletLimit::with('currency:id,iso_code,symbol,name')->findOrFail($id);

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
            'data'    => $walletLimit->fresh()->load('currency:id,iso_code,symbol,name'),
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
            'data'    => $walletLimit->fresh()->load('currency:id,iso_code,symbol,name'),
        ]);
    }
}
