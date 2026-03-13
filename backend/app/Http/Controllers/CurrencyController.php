<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the currencies.
     */
    public function index(Request $request): Response
    {
        $currencies = Currency::with('country')
            ->orderBy('name')
            ->get();

        $countries = Country::orderBy('name')->get(['id', 'name', 'iso_code_2', 'iso_code_3']);

        $roleUser = Auth::user()?->roles?->first()?->prefix ?? 'guest';

        return Inertia::render('Currencies/Index', [
            'currencies' => $currencies,
            'countries'  => $countries,
            'roleUser'   => $roleUser,
            'tokenId'    => $request->get('token'),
        ]);
    }

    /**
     * Store a newly created currency.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'iso_code' => 'required|string|max:10|unique:currencies,iso_code',
            'symbol' => 'nullable|string|max:12',
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:FIAT,CRYPTO,TOKEN',
            'country_id' => 'nullable|exists:countries,id',
            'decimal_places' => 'required|integer|min:0|max:10',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $currency = Currency::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Moneda creada exitosamente',
            'data' => $currency->load('country')
        ]);
    }

    /**
     * Display the specified currency.
     */
    public function show(string $id): JsonResponse
    {
        $currency = Currency::with('country')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $currency
        ]);
    }

    /**
     * Update the specified currency.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $currency = Currency::findOrFail($id);

        $validated = $request->validate([
            'iso_code' => 'required|string|max:10|unique:currencies,iso_code,' . $id,
            'symbol' => 'nullable|string|max:12',
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:FIAT,CRYPTO,TOKEN',
            'country_id' => 'nullable|exists:countries,id',
            'decimal_places' => 'required|integer|min:0|max:10',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $currency->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Moneda actualizada exitosamente',
            'data' => $currency->load('country')
        ]);
    }

    /**
     * Soft delete the specified currency.
     */
    public function delete(string $id): JsonResponse
    {
        $currency = Currency::findOrFail($id);
        
        // Check if currency has related exchange rates
        if ($currency->exchangeRate()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la moneda porque tiene tasas de cambio asociadas'
            ], 422);
        }

        $currency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Moneda eliminada exitosamente'
        ]);
    }

    /**
     * Fetch list of currencies for AJAX requests.
     */
    public function fetchList(Request $request): JsonResponse
    {
        $currencies = Currency::with('country')
            ->when($request->get('type'), function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->get('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $currencies
        ]);
    }

    /**
     * Toggle currency status.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $currency = Currency::findOrFail($id);
        
        $newStatus = $currency->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $currency->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Estado de la moneda actualizado exitosamente',
            'data' => $currency->fresh()
        ]);
    }
}
