<?php

namespace App\Http\Controllers;

use App\Factories\ExchangeRate\ExchangeRateFactory;
use App\Http\Requests\ExchangeRate\ExchangeRateRequest;
use App\Http\Support\PublicTokenResolver;
use App\Models\ExchangeRate;
use App\Models\UserPreference;
use App\Services\ExchangeRate\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador para la gestión de tasas de cambio.
 *
 * Orquesta peticiones HTTP y delega la lógica al dominio,
 * utilizando manejo centralizado de errores.
 *
 * - Rutas auth: no requieren token (usuario autenticado)
 * - Rutas public: el token es validado por PublicTokenMiddleware
 */
class ExchangeRateController extends Controller
{
    public function __construct(
        private ExchangeRateFactory $factory,
        private ExchangeRateService $service,
        private PublicTokenResolver $tokenResolver,
    ) {
    }

    /**
     * Eliminar una tasa de cambio (soft delete).
     */
    public function delete(string $id) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Tasa de cambio eliminada correctamente',
                'data' => $this->service->delete($id),
            ],
            'No se pudo eliminar la tasa de cambio.',
        );
    }

    /**
     * Desactivar la actualización automática de la tasa VES usando el BCV.
     */
    public function disableBcvAutoUpdate(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            function() use ($request) {
                $this->service->disableBcvAutoUpdate($this->resolveToken($request));

                return [
                    'message' => 'Actualización automática vía BCV desactivada.',
                    'enabled' => false,
                ];
            },
            'No se pudo desactivar la actualización automática vía BCV.',
        );
    }

    /**
     * Desactivar el cronjob de actualización automática.
     */
    public function disableCronjob(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            function() {
                if (! ExchangeRate::exists()) {
                    return response()->json([
                        'message' => 'No hay tasas de cambio registradas.',
                    ], 422);
                }

                UserPreference::setPreference(
                    auth()->id(),
                    UserPreference::CATEGORY_EXCHANGE_RATE,
                    'auto_update_enabled',
                    'false',
                );

                return [
                    'message' => 'Cronjob de actualización automática desactivado.',
                    'enabled' => false,
                ];
            },
            'No se pudo desactivar el cronjob de actualización automática.',
        );
    }

    /**
     * Desactivar la actualización automática de una moneda individual.
     */
    public function disableCurrencyAutoUpdate(Request $request, string $isoCode) : JsonResponse
    {
        return $this->handleServiceJson(
            function() use ($request, $isoCode) {
                $this->service->disableCurrencyAutoUpdate($isoCode, $this->resolveToken($request));

                return [
                    'message' => "Actualización automática de {$isoCode} desactivada.",
                    'enabled' => false,
                ];
            },
            'No se pudo desactivar la actualización automática de la moneda.',
        );
    }

    /**
     * Activar la actualización automática de la tasa VES usando el BCV.
     */
    public function enableBcvAutoUpdate(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            function() use ($request) {
                $this->service->enableBcvAutoUpdate($this->resolveToken($request));

                return [
                    'message' => 'Actualización automática vía BCV activada.',
                    'enabled' => true,
                ];
            },
            'No se pudo activar la actualización automática vía BCV.',
        );
    }

    /**
     * Activar el cronjob de actualización automática.
     */
    public function enableCronjob(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            function() {
                if (! ExchangeRate::exists()) {
                    return response()->json([
                        'message' => 'Debe registrar al menos una tasa de cambio para activar la actualización automática.',
                    ], 422);
                }

                UserPreference::setPreference(
                    auth()->id(),
                    UserPreference::CATEGORY_EXCHANGE_RATE,
                    'auto_update_enabled',
                    'true',
                );

                return [
                    'message' => 'Cronjob de actualización automática activado.',
                    'enabled' => true,
                ];
            },
            'No se pudo activar el cronjob de actualización automática.',
        );
    }

    /**
     * Activar la actualización automática de una moneda individual (todas
     * excepto VES, ver enableBcvAutoUpdate).
     */
    public function enableCurrencyAutoUpdate(Request $request, string $isoCode) : JsonResponse
    {
        return $this->handleServiceJson(
            function() use ($request, $isoCode) {
                $this->service->enableCurrencyAutoUpdate($isoCode, $this->resolveToken($request));

                return [
                    'message' => "Actualización automática de {$isoCode} activada.",
                    'enabled' => true,
                ];
            },
            'No se pudo activar la actualización automática de la moneda.',
        );
    }

    /**
     * Descarga en CSV el mismo histórico que ve Historical.vue — reusa
     * historicalContext() (mismo scoping por rol que la vista Inertia, ver
     * ExchangeRateContextService::resolveHistorical()) para no duplicar la
     * lógica de qué filas puede ver cada rol.
     *
     * @param string $id ID de la moneda (currency_id)
     */
    public function exportHistorical(Request $request, string $id) : StreamedResponse
    {
        $context = $this->service->historicalContext($id, $this->resolveToken($request));
        $rows = $context['exchangeRates'] ?? [];
        $isAdmin = in_array($context['roleUser'] ?? null, ['admin', 'superadmin'], true);

        $filename = 'historico-tasa-cambio-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function() use ($rows, $isAdmin) {
            $handle = fopen('php://output', 'w');

            $header = ['Moneda', 'Cambio', 'Porcentaje', 'Estado', 'Fecha'];
            if ($isAdmin) {
                $header[] = 'Origen';
                $header[] = 'Registrado por';
            }
            fputcsv($handle, $header);

            foreach ($rows as $rate) {
                $row = [
                    $rate->currency_name ?? '-',
                    number_format((float) $rate->amount, 4, '.', ''),
                    $rate->percentage,
                    $rate->status,
                    optional($rate->created_at)->format('Y-m-d H:i:s'),
                ];

                if ($isAdmin) {
                    $row[] = $rate->source === ExchangeRate::SOURCE_SYSTEM ? 'Automático' : 'Manual';
                    $row[] = $rate->user_name ?? '-';
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Listado filtrado por rol/token.
     */
    public function fetchList(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'data' => $this->service->getAll($this->resolveToken($request)),
            ],
            'No se pudo cargar la lista de tasas de cambio.',
        );
    }

    /**
     * Obtener el estado actual de actualización automática.
     */
    public function getAutoUpdateStatus(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            function() {
                $enabled = UserPreference::getPreference(
                    auth()->id(),
                    UserPreference::CATEGORY_EXCHANGE_RATE,
                    'auto_update_enabled',
                    false,
                );

                return [
                    'enabled' => (bool) $enabled,
                ];
            },
            'No se pudo obtener el estado del cronjob de actualización automática.',
        );
    }

    /**
     * Obtener el estado actual de la actualización automática vía BCV.
     */
    public function getBcvAutoUpdateStatus(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'enabled' => $this->service->getBcvAutoUpdateStatus($this->resolveToken($request)),
            ],
            'No se pudo obtener el estado de la actualización automática vía BCV.',
        );
    }

    /**
     * Consultar la tasa oficial USD/VES publicada por el BCV.
     */
    public function getBcvRate(Request $request) : JsonResponse
    {
        $rate = $this->service->getBcvRate();

        if ($rate === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la tasa del BCV. Intente más tarde.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tasa BCV obtenida correctamente.',
            'data' => ['rate' => $rate],
        ]);
    }

    /**
     * Obtener el estado actual de la actualización automática de una moneda individual.
     */
    public function getCurrencyAutoUpdateStatus(Request $request, string $isoCode) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'enabled' => $this->service->getCurrencyAutoUpdateStatus($isoCode, $this->resolveToken($request)),
            ],
            'No se pudo obtener el estado de la actualización automática de la moneda.',
        );
    }

    /**
     * Obtener tasas crudas desde proveedor externo.
     */
    public function getRawRates(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Tasas obtenidas correctamente.',
                'data' => $this->service->getRawRates($this->resolveToken($request)) ?? [],
            ],
            'No se pudieron obtener las tasas de cambio.',
        );
    }

    /**
     * Histórico de tasas de cambio por moneda (Inertia).
     *
     * @param string $id ID de la moneda (currency_id)
     */
    public function historical(Request $request, string $id) : Response
    {
        return $this->handleServiceInertia(
            fn () => Inertia::render(
                'ExchangeRate/Historical',
                [
                    ...$this->service->historicalContext($id, $this->resolveToken($request)),
                    'title' => 'Histórico de Tasas de Cambio',
                ],
            ),
            'No se pudo cargar el histórico de tasas de cambio.',
        );
    }

    /**
     * Listado principal (Inertia).
     */
    public function index(Request $request) : Response
    {
        return $this->handleServiceInertia(
            fn () => Inertia::render(
                'ExchangeRate/Index',
                [
                    ...$this->service->context($this->resolveToken($request)),
                    'title' => 'Tasas de Cambio',
                ],
            ),
            'No se pudo cargar la lista de tasas de cambios.',
        );
    }

    /**
     * Mostrar detalle de una tasa de cambio.
     */
    public function show(string $id) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => ['data' => $this->service->show($id)],
            'No se pudo cargar la información de la tasa de cambio.',
        );
    }

    /**
     * Crear una nueva tasa de cambio.
     */
    public function store(ExchangeRateRequest $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Registro exitoso',
                'data' => $this->service->create(
                    $this->factory->fromRequest($request->validated()),
                ),
            ],
            'Hubo un error al registrar la tasa de cambio.',
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE).
     */
    public function toggleStatus(string $id) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Estado actualizado correctamente',
                'data' => $this->service->toggleStatus($id),
            ],
            'No se pudo cambiar el estado de la tasa de cambio.',
        );
    }

    /**
     * Actualizar una tasa de cambio.
     */
    public function update(ExchangeRateRequest $request, string $id) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Actualización exitosa',
                'data' => $this->service->update(
                    $this->factory->fromRequest(
                        $request->validated(),
                        $id,
                    ),
                ),
            ],
            'No se pudo actualizar la tasa de cambio.',
        );
    }

    /**
     * Actualizar todas las tasas con los valores confirmados por el usuario.
     */
    public function updateAll(Request $request) : JsonResponse
    {
        $rates = $request->input('rates', []);

        return $this->handleServiceJson(
            fn () => [
                'message' => 'Tasas de cambio actualizadas correctamente.',
                'data' => $this->service->updateRates($rates, $this->resolveToken($request)),
            ],
            'No se pudo actualizar los valores de las tasas de cambio.',
        );
    }

    /**
     * Obtiene el token público desde el request (si existe).
     */
    private function resolveToken(Request $request) : ?string
    {
        return $this->tokenResolver->resolve($request);
    }
}
