<?php

namespace App\Http\Controllers\Games\Bingo\Card;

use App\Domains\Games\Bingo\DTOs\Card\Generate\BingoCardGenerateDTO;
use App\Domains\Games\Bingo\Services\Card\Generate\BingoSellerPosService;
use App\Services\Auth\PermissionService;
use App\Services\Roles\Player\External\ExternalBuyerPurchaseCodeService;
use App\Services\Roles\Seller\SellerSalesLinkService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Card\BingoCardGenerateRequest;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de Cartones de Bingo.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class BingoCardController extends Controller
{
    // Sin authorizeResource(): ninguna acción es un método de recurso estándar
    // (index/show/store/…), así que nunca aplicaba. La autorización real vive
    // en BingoCardGenerateRequest::authorize(), ensureSeller() y las
    // validaciones de sala/país del servicio.
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly \App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService $generateService,
        private readonly BingoSellerPosService $posService,
    ) {}

    /**
     * Vista para generar cartones de bingo.
     *
     * @return Response
     */
    public function viewGenerateCard(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $userId = $this->userContext->resolveUserId();
                $context = $this->generateService->viewContext($userId)->toView();

                return Inertia::render('Games/Bingo/Card/Generate', $context);
            },
            'No se pudo cargar la vista para generar cartones de bingo.'
        );
    }

    /**
     * Generar cartones de bingo para un jugador.
     *
     * El `{room}` en la URL usa route model binding (HasHashedRouteKey),
     * por lo que el hash se decodifica automáticamente antes de llegar aquí.
     *
     * @param BingoRoom                $room    Sala decodificada por route model binding.
     * @param BingoCardGenerateRequest $request Body con card_count, country_id y player.
     * @return JsonResponse
     */
    public function generateCardStore(BingoRoom $room, BingoCardGenerateRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->generateService->generate(
                BingoCardGenerateDTO::fromArray($request->validated())
            )
        );
    }

    /**
     * Punto de venta: todas las salas vendibles de una fecha en una sola
     * llamada (el cliente filtra por tipo de sala / conexión / bote).
     */
    public function rooms(Request $request): JsonResponse
    {
        $this->ensureSeller();
        $validated = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        return $this->handleServiceJson(
            fn () => ['rooms' => $this->generateService->listSellableRooms($validated['date'])]
        );
    }

    /**
     * Punto de venta: compradores del vendedor para autocompletar.
     */
    public function frequentBuyers(Request $request): JsonResponse
    {
        $this->ensureSeller();
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:60']]);

        return $this->handleServiceJson(fn () => [
            'buyers' => $this->posService->frequentBuyers(
                (string) $this->userContext->resolveUserId(),
                $validated['q'] ?? null,
            ),
        ]);
    }

    /**
     * Punto de venta: ventas del día del vendedor y sus totales.
     */
    public function todaySales(): JsonResponse
    {
        $this->ensureSeller();

        return $this->handleServiceJson(
            fn () => $this->posService->todaySales((string) $this->userContext->resolveUserId())
        );
    }

    // ─── Enlace de venta y códigos de compra (compra por cuenta propia) ─────

    /**
     * Enlace público de venta del vendedor + opciones para otorgar códigos.
     */
    public function salesLink(SellerSalesLinkService $links): JsonResponse
    {
        $this->ensureSeller();

        return $this->handleServiceJson(fn () => [
            'link'          => $links->linkFor($this->currentSeller()),
            'code_options'  => [
                'uses'               => config('sellers.purchase_codes.uses_options'),
                'default_uses'       => config('sellers.purchase_codes.default_uses'),
                'valid_days'         => config('sellers.purchase_codes.valid_days_options'),
                'default_valid_days' => config('sellers.purchase_codes.default_valid_days'),
            ],
        ]);
    }

    /** Regenera el enlace (el anterior deja de funcionar). */
    public function regenerateSalesLink(SellerSalesLinkService $links): JsonResponse
    {
        $this->ensureSeller();

        return $this->handleServiceJson(fn () => ['link' => $links->regenerate($this->currentSeller())]);
    }

    /** Códigos de compra otorgados por el vendedor. */
    public function purchaseCodes(ExternalBuyerPurchaseCodeService $codes): JsonResponse
    {
        $this->ensureSeller();

        return $this->handleServiceJson(fn () => [
            'codes' => $codes->listForSeller((string) $this->userContext->resolveUserId()),
        ]);
    }

    /**
     * Otorga un código a un comprador recurrente (ya le compró antes a este
     * vendedor). Revoca el código vigente previo de ese comprador.
     */
    public function issuePurchaseCode(Request $request, ExternalBuyerPurchaseCodeService $codes): JsonResponse
    {
        $this->ensureSeller();

        $validated = $request->validate([
            'country_id' => ['required', 'string', 'exists:countries,id'],
            'phone'      => ['required', 'string', 'max:25'],
            'max_uses'   => ['required', 'integer', \Illuminate\Validation\Rule::in(config('sellers.purchase_codes.uses_options'))],
            'valid_days' => ['required', 'integer', \Illuminate\Validation\Rule::in(config('sellers.purchase_codes.valid_days_options'))],
        ]);

        return $this->handleServiceJson(fn () => [
            'code' => $codes->present($codes->issue(
                (string) $this->userContext->resolveUserId(),
                $validated['country_id'],
                $validated['phone'],
                (int) $validated['max_uses'],
                (int) $validated['valid_days'],
            )),
        ]);
    }

    /** Revoca un código propio. */
    public function revokePurchaseCode(string $purchaseCode, ExternalBuyerPurchaseCodeService $codes): JsonResponse
    {
        $this->ensureSeller();

        return $this->handleServiceJson(function () use ($purchaseCode, $codes) {
            $codes->revoke((string) $this->userContext->resolveUserId(), $purchaseCode);

            return ['message' => 'Código revocado.'];
        });
    }

    private function currentSeller(): \App\Models\Roles\Seller
    {
        return \App\Models\Roles\Seller::where('user_id', $this->userContext->resolveUserId())->firstOrFail();
    }

    /**
     * Los endpoints del punto de venta exponen datos personales de
     * compradores: solo el vendedor (mismo criterio que
     * BingoCardGenerateRequest::authorize()).
     */
    private function ensureSeller(): void
    {
        abort_unless(PermissionService::checkPrefix(['seller']), 403, 'Solo los vendedores pueden usar el punto de venta.');
    }

    /**
     * Obtener lista de paises disponibles para el vendedor.
     * - En base a las monedas permitidas de su manager_user_id, si aplica
     * - En base a las monedas permitidas de la sala de bingo
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function availableCountries(BingoRoom $room): JsonResponse
    {
        $userId = $this->userContext->resolveUserId();

        return $this->handleServiceJson(
            fn() => $this->generateService->getAvailableCountries($room->id, $userId)
        );
    }

    /**
     * Obtener el precio del cartón para una sala y país específicos.
     *
     * El precio se calcula aplicando:
     * - Precio personalizado en bingo_card_prices (si existe para la sala y moneda del país).
     * - En su defecto, precio base de la sala (card_price_usd) × tasa de cambio del commerce.
     *
     * @param BingoRoom $room    Sala de bingo (model binding por {room}).
     * @param Request   $request Parámetros de la petición; debe incluir `country_id` (string Snowflake).
     * @return JsonResponse      Respuesta con card_price, currency_symbol, exchange_rate, is_custom_price.
     */
    public function cardPrice(BingoRoom $room, Request $request): JsonResponse
    {
        $userId    = $this->userContext->resolveUserId();
        $countryId = (string) $request->query('country_id');

        return $this->handleServiceJson(
            fn() => $this->generateService->getCardPrice($room->id, $userId, $countryId)
        );
    }
}
