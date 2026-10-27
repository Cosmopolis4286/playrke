<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Bingo\Card;

use App\Domains\Games\Bingo\DTOs\Card\Generate\BingoCardGenerateDTO;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService;
use App\Http\Controllers\Controller;
use App\Models\Roles\Player\External\ExternalBuyerPurchaseCode;
use App\Models\Roles\Seller;
use App\Services\Roles\Player\External\ExternalBuyerPurchaseCodeService;
use App\Services\Roles\Seller\SellerSalesLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Compra por cuenta propia de un comprador RECURRENTE, desde el enlace
 * público de su vendedor (/comprar/{token}) — sin cuenta en la plataforma.
 *
 * Regla (2026-10-03): solo quien ya le compró antes al vendedor, con un
 * código de compra vigente que el vendedor le otorgó (vence por usos o por
 * tiempo). Sin código válido la solicitud no se procesa.
 *
 * Flujo: verify (país + teléfono + código → sesión de compra de 30 min) →
 * rooms → price → purchase (comprobante obligatorio: pago móvil o
 * transferencia). La venta se registra EXACTAMENTE como una del punto de
 * venta (BingoCardGenerateService::generate): a nombre del vendedor y su
 * comercio, con su comisión, precio verificado, comprobante único y pago
 * PENDING hasta que el comercio lo apruebe.
 */
final class PublicBingoBuyController extends Controller
{
    private const SESSION_TTL_MINUTES = 30;

    public function __construct(
        private readonly SellerSalesLinkService $links,
        private readonly ExternalBuyerPurchaseCodeService $codes,
        private readonly BingoCardGenerateService $generateService,
    ) {
    }

    /** Cierra la sesión de compra (botón "Salir"). */
    public function forget(Request $request, string $token) : JsonResponse
    {
        $request->session()->forget($this->sessionKey($token));

        return response()->json(['success' => true]);
    }

    /** Precio del cartón en la moneda del comprador (sin datos internos de comisión). */
    public function price(Request $request, string $token, BingoRoom $room) : JsonResponse
    {
        $seller = $this->sellerOrFail($token);
        $session = $this->requireSession($request, $token, $seller);

        return $this->asSeller($seller, fn () => $this->handleServiceJson(function() use ($room, $seller, $session) {
            $price = $this->generateService->getCardPrice((string) $room->id, (string) $seller->user_id, $session['country_id']);

            return [
                'card_price' => $price['card_price'],
                'currency_symbol' => $price['currency_symbol'],
                'exchange_rate' => $price['exchange_rate'],
            ];
        }));
    }

    /**
     * Registra la compra. Consume un uso del código (atómico, con lock por
     * código) y lo devuelve si la venta no llega a registrarse.
     */
    public function purchase(Request $request, string $token, BingoRoom $room) : JsonResponse
    {
        $seller = $this->sellerOrFail($token);
        $session = $this->requireSession($request, $token, $seller);

        $validated = $request->validate([
            'card_count' => ['required', 'integer', 'min:1', 'max:40'],
            // Compra a distancia: siempre con comprobante (no hay efectivo).
            'payment_method' => ['required', Rule::in([BingoCardGenerateDTO::PAYMENT_MOBILE_PAYMENT, BingoCardGenerateDTO::PAYMENT_BANK_TRANSFER])],
            'receipt_number' => ['required', 'string', 'max:100'],
            'receipt_image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'expected_card_price' => ['required', 'numeric', 'min:0'],
        ], [
            'receipt_number.required' => 'Indica el número de referencia de tu pago.',
            'receipt_image.required' => 'Sube la foto del comprobante de pago.',
            'receipt_image.max' => 'La imagen no puede superar los 2 MB.',
        ]);

        $buyer = $this->codes->previousBuyer((string) $seller->user_id, $session['country_id'], $session['phone']);
        abort_unless($buyer !== null, 403, 'No se encontró tu historial de compras con este vendedor.');

        $lock = Cache::lock('bingo-buy:code:' . $session['code_id'], 30);

        if (! $lock->get()) {
            return response()->json(['message' => 'Ya hay una compra en curso con este código. Espera unos segundos.'], 409);
        }

        try {
            $code = ExternalBuyerPurchaseCode::find($session['code_id']);

            if (! $code || ! $this->codes->consume($code)) {
                $request->session()->forget($this->sessionKey($token));

                return response()->json(['message' => 'Tu código ya no es válido (vencido, agotado o revocado). Pídele uno nuevo a tu vendedor.'], 422);
            }

            try {
                $result = $this->asSeller($seller, fn () => $this->generateService->generate(BingoCardGenerateDTO::fromArray([
                    'room_id' => (string) $room->id,
                    'card_count' => $validated['card_count'],
                    'country_id' => $session['country_id'],
                    'player' => [
                        'phone' => $buyer['phone'],
                        'first_name' => $buyer['first_name'],
                        'last_name' => $buyer['last_name'],
                    ],
                    'payment_method' => $validated['payment_method'],
                    'receipt_number' => $validated['receipt_number'],
                    'receipt_image' => $validated['receipt_image'],
                    'expected_card_price' => $validated['expected_card_price'],
                ])));
            } catch (Throwable $e) {
                $this->codes->releaseUse($code);

                throw $e;
            }
        } finally {
            $lock->release();
        }

        return response()->json([
            'success' => true,
            'message' => 'Recibimos tu compra. Tus cartones aparecerán en tu enlace cuando se confirme el pago.',
            'playerLink' => $result['playerLink'] ?? null,
            'paymentStatus' => $result['paymentStatus'] ?? 'PENDING',
            'remaining_uses' => $code->fresh()?->remainingUses() ?? 0,
        ]);
    }

    /** Salas del vendedor abiertas a la venta en una fecha. */
    public function rooms(Request $request, string $token) : JsonResponse
    {
        $seller = $this->sellerOrFail($token);
        $this->requireSession($request, $token, $seller);

        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date'] ?? now()->toDateString();

        return $this->asSeller($seller, fn () => $this->handleServiceJson(fn () => [
            'rooms' => collect($this->generateService->listSellableRooms($date))
                ->filter(fn (array $room) => (bool) ($room['sales_open'] ?? false))
                ->map(fn (array $room) => [
                    'id' => $room['id'],
                    'uuid_code' => $room['uuid_code'],
                    'time' => $room['time'] ?? null,
                    'is_manual' => $room['is_manual'] ?? false,
                    'starts_at' => $room['starts_at'] ?? null,
                    'sales_close_at' => $room['sales_close_at'] ?? null,
                    'prize_pool' => $room['prize_pool'],
                    'total_players' => $room['total_players'],
                ])
                ->values()
                ->all(),
        ]));
    }

    /** Página pública de compra del vendedor. */
    public function show(string $token) : Response
    {
        $seller = $this->sellerOrFail($token);

        return Inertia::render('Public/BingoBuyWithCode', [
            'token' => $token,
            'sellerName' => $seller->name,
            'commerceName' => $seller->resolveCommerce()?->name,
            'countries' => $this->sellerCountries($seller),
        ]);
    }

    /**
     * Verifica país + teléfono + código. Con límite de intentos por enlace +
     * teléfono y por IP (adivinar códigos), y bloqueo temporal.
     */
    public function verify(Request $request, string $token) : JsonResponse
    {
        $seller = $this->sellerOrFail($token);

        $validated = $request->validate([
            'country_id' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:25'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        $max = (int) config('sellers.purchase_codes.max_failed_attempts');
        $decay = (int) config('sellers.purchase_codes.lockout_minutes') * 60;
        $phoneKey = 'bingo-buy:verify:' . $token . ':' . $this->codes->digits($validated['phone']);
        $ipKey = 'bingo-buy:verify-ip:' . $request->ip();

        foreach ([$phoneKey, $ipKey] as $key) {
            if (RateLimiter::tooManyAttempts($key, $key === $ipKey ? $max * 4 : $max)) {
                return response()->json([
                    'message' => 'Demasiados intentos. Vuelve a intentarlo en ' . ceil(RateLimiter::availableIn($key) / 60) . ' minuto(s).',
                ], 429);
            }
        }

        $code = $this->codes->findUsable((string) $seller->user_id, $validated['country_id'], $validated['phone'], $validated['code']);

        if (! $code || ! $this->codes->isRecurringBuyer((string) $seller->user_id, $validated['country_id'], $validated['phone'])) {
            RateLimiter::hit($phoneKey, $decay);
            RateLimiter::hit($ipKey, $decay);

            // Mensaje único: no revelar si falló el teléfono o el código.
            return response()->json(['message' => 'Los datos o el código no son válidos. Pídele un código vigente a tu vendedor.'], 422);
        }

        RateLimiter::clear($phoneKey);

        $request->session()->put($this->sessionKey($token), [
            'code_id' => $code->id,
            'country_id' => $validated['country_id'],
            'phone' => $validated['phone'],
            'expires' => now()->addMinutes(self::SESSION_TTL_MINUTES)->timestamp,
        ]);

        $buyer = $this->codes->previousBuyer((string) $seller->user_id, $validated['country_id'], $validated['phone']);

        return response()->json([
            'success' => true,
            'buyer_name' => trim(($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? '')),
            'remaining_uses' => $code->remainingUses(),
            'expires_at' => $code->expires_at?->toIso8601String(),
        ]);
    }

    /**
     * Ejecuta como el vendedor SOLO para esta request (sin tocar la sesión):
     * la lógica de venta atribuye todo al usuario autenticado (vendedor,
     * su red, su comisión). Se restaura el usuario previo al terminar.
     */
    private function asSeller(Seller $seller, callable $callback) : mixed
    {
        $guard = Auth::guard();
        $previous = $guard->user();

        $guard->onceUsingId($seller->user_id);

        try {
            return $callback();
        } finally {
            if ($previous) {
                $guard->setUser($previous);
            } else {
                $guard->forgetUser();
            }
        }
    }

    /**
     * Sesión de compra vigente para este enlace, con su código aún usable
     * (pudo revocarse o vencer después de verificar).
     *
     * @return array{code_id: string, country_id: string, phone: string, expires: int}
     */
    private function requireSession(Request $request, string $token, Seller $seller) : array
    {
        $session = $request->session()->get($this->sessionKey($token));

        $valid = is_array($session)
            && ($session['expires'] ?? 0) > now()->timestamp
            && ExternalBuyerPurchaseCode::whereKey($session['code_id'] ?? null)
                ->where('seller_user_id', (string) $seller->user_id)
                ->usable()
                ->exists();

        if (! $valid) {
            $request->session()->forget($this->sessionKey($token));
            abort(401, 'Tu sesión de compra venció o tu código ya no es válido. Ingresa de nuevo tu código.');
        }

        return $session;
    }

    /** @return array<int, array{id: string, name: string, iso_code_2: string}> */
    private function sellerCountries(Seller $seller) : array
    {
        $currencyIds = app(\App\Contracts\Roles\Seller\SellerCurrencyRepositoryInterface::class)
            ->getAvailableCurrencies((string) $seller->id, true);
        $countryIds = app(\App\Contracts\CurrencyRepositoryInterface::class)
            ->getCountriesByCurrencies(array_map('strval', $currencyIds));

        return collect(app(\App\Contracts\CountryRepositoryInterface::class)->getByIds($countryIds, ['id', 'name', 'iso_code_2']))
            ->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->name, 'iso_code_2' => (string) $c->iso_code_2])
            ->values()
            ->all();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function sellerOrFail(string $token) : Seller
    {
        $seller = $this->links->resolveSeller($token);

        abort_unless($seller !== null, 404, 'Este enlace de compra no existe o ya no está activo.');

        return $seller;
    }

    private function sessionKey(string $token) : string
    {
        return 'bingo_buy.' . $token;
    }
}
