<?php

namespace App\Http\Controllers\Games\Bingo\Report;

use App\DTOs\Games\Bingo\Card\BingoCardGenerateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Card\BingoCardGenerateRequest;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Services\Games\Bingo\Card\Generate\BingoCardGenerateService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración del juego de Bingo.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class BingoReportController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly BingoCardGenerateService $generateService,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /**
     * Display the cards sold report page.
     */
    public function cardsSold(string $currency = null, string $dateFilter = null): Response
    {
        // TODO: Implement actual data fetching logic
        // This is a placeholder implementation
        $cardsData = [
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true],
                ['key' => 'card_number', 'label' => 'Número de Cartón', 'sortable' => true],
                ['key' => 'room_name', 'label' => 'Sala', 'sortable' => true],
                ['key' => 'player_name', 'label' => 'Jugador', 'sortable' => true],
                ['key' => 'sale_date', 'label' => 'Fecha de Venta', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Monto', 'sortable' => true],
                ['key' => 'currency', 'label' => 'Moneda', 'sortable' => true],
                ['key' => 'status', 'label' => 'Estado', 'sortable' => true],
            ],
            'rows' => [
                // Placeholder data - replace with actual database query
                [
                    'id' => 1,
                    'card_number' => 'BINGO-001',
                    'room_name' => 'Sala Principal',
                    'player_name' => 'Juan Pérez',
                    'sale_date' => '2024-01-15 10:30:00',
                    'amount' => 10.00,
                    'currency' => 'USD',
                    'status' => 'Active'
                ],
                [
                    'id' => 2,
                    'card_number' => 'BINGO-002',
                    'room_name' => 'Sala Secundaria',
                    'player_name' => 'María García',
                    'sale_date' => '2024-01-15 11:45:00',
                    'amount' => 15.00,
                    'currency' => 'USD',
                    'status' => 'Active'
                ]
            ]
        ];

        $currencies = [
            ['id' => 1, 'iso_code' => 'USD'],
            ['id' => 2, 'iso_code' => 'EUR'],
            ['id' => 3, 'iso_code' => 'VES']
        ];

        return Inertia::render('Games/Bingo/Report/CardsSold', [
            'title' => 'Reporte de Cartones Vendidos',
            'cards' => $cardsData,
            'currencies' => $currencies,
            'selectedCurrency' => $currency,
            'dateFilter' => $dateFilter
        ]);
    }

    
}
