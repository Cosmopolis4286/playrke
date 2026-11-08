<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Platform\DepositAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * D1: catálogo de cuentas de la plataforma para depositar (admin/tesorería,
 * wallet.movements.manage) y la lista que ve el jugador en el paso de
 * depósito (activeForCurrency).
 */
final class DepositAccountController extends Controller
{
    /** GET /wallet/deposit-accounts?currency_id= — cuentas activas para depositar en esa moneda. */
    public function activeForCurrency(Request $request) : JsonResponse
    {
        $validated = $request->validate(['currency_id' => ['required', 'string']]);

        return response()->json([
            'success' => true,
            'data' => DepositAccount::activeFor($validated['currency_id'])->get()->map->toPublicArray()->values(),
        ]);
    }

    public function destroy(string $id) : JsonResponse
    {
        DepositAccount::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Cuenta eliminada.']);
    }

    public function index() : Response
    {
        return Inertia::render('Wallet/DepositAccounts/Index', [
            'accounts' => DepositAccount::with('currency:id,iso_code,symbol')
                ->orderBy('currency_id')->orderBy('sort_order')->orderBy('label')
                ->get()
                ->map(fn (DepositAccount $a) => $a->toPublicArray() + [
                    'currency_id' => $a->currency_id,
                    'currency_code' => $a->currency?->iso_code,
                    'status' => $a->status,
                    'sort_order' => $a->sort_order,
                ])
                ->values(),
            'currencies' => Currency::query()->orderBy('iso_code')->get(['id', 'iso_code', 'name'])
                ->map(fn ($c) => ['id' => (string) $c->id, 'iso_code' => $c->iso_code, 'name' => $c->name])
                ->values(),
            'methods' => collect(DepositAccount::METHODS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
        ]);
    }

    public function store(Request $request) : JsonResponse
    {
        $account = DepositAccount::create($this->validated($request));

        return response()->json(['success' => true, 'message' => 'Cuenta creada.', 'data' => ['id' => (string) $account->id]]);
    }

    public function update(Request $request, string $id) : JsonResponse
    {
        DepositAccount::findOrFail($id)->update($this->validated($request));

        return response()->json(['success' => true, 'message' => 'Cuenta actualizada.']);
    }

    private function validated(Request $request) : array
    {
        return $request->validate([
            'currency_id' => ['required', 'string', Rule::exists('currencies', 'id')],
            'method' => ['required', Rule::in(array_keys(DepositAccount::METHODS))],
            'label' => ['required', 'string', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:80'],
            'holder_name' => ['required', 'string', 'max:120'],
            'holder_document' => ['nullable', 'string', 'max:40'],
            'account_identifier' => ['required', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]) + ['sort_order' => 0];
    }
}
