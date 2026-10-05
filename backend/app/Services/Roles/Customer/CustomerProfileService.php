<?php

namespace App\Services\Roles\Customer;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Roles\Customer;
use App\Models\Roles\Customer\CustomerUser;
use App\Services\Roles\AdminProfileAccessService;
use App\Services\Roles\RoleProfileEnrichmentService;
use App\Services\Roles\RoleProfileInsightsService as Insights;
use App\Services\Security\UserContextService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Autogestión de la ficha propia de un Customer ("Mi Cliente": integración
 * B2B vía API) — distinto de AdminCustomerService (gestión de terceros,
 * customers.*). Mismo patrón que Commerce/Subsidiary/SellerProfileService:
 * por defecto resuelve el Customer del usuario autenticado; admin/superadmin
 * pueden inspeccionar uno ajeno vía $targetUserId (AdminProfileAccessService).
 *
 * El Customer no tiene red comercial (ni vendedores ni agencias): su
 * actividad es la de las salas de bingo que CREA (ventas en ellas y su
 * comisión de sala) y la de los usuarios que integra por API (CustomerUser).
 */
final class CustomerProfileService
{
    /**
     * Comisión de sala de cada lote, en moneda local, tal como la registró
     * RoomMetricsService al vender (financial_breakdown.room_local) — no hay
     * columna propia: la comisión del creador se acumula por sala, no por lote.
     */
    private const ROOM_COMMISSION_SQL = "CAST(JSON_UNQUOTE(JSON_EXTRACT(financial_breakdown, '$.room_local.room_commission')) AS DECIMAL(15,3))";

    public function __construct(
        private readonly UserContextService $userContext,
        private readonly AdminProfileAccessService $adminAccess,
        private readonly RoleProfileEnrichmentService $enrichment,
        private readonly Insights $insights,
    ) {}

    private function ownCustomerOrFail(?string $targetUserId = null): Customer
    {
        $access = $this->adminAccess->resolve($this->userContext->resolveUserId(), $targetUserId);

        $customer = Customer::where('user_id', $access['owner_user_id'])->first();

        abort_if(!$customer, 404, 'No se encontró un cliente asociado a este usuario.');

        return $customer;
    }

    public function showOwn(?string $targetUserId = null): array
    {
        $customer = $this->ownCustomerOrFail($targetUserId);
        $customer->load(['country:id,name', 'currency:id,iso_code,name,symbol', 'user:id,username,email']);

        $userId = (string) $customer->user_id;
        $roomIds = BingoRoom::where('creator_user_id', $userId)->select('id');
        $lotScope = fn (Builder $q) => $q->whereIn('room_id', $roomIds);

        $currencies = collect([$customer->currency?->iso_code])
            ->merge($customer->otherCurrencies()->pluck('iso_code'))
            ->filter()->unique()->values()->all();

        $integratedUsers = CustomerUser::where('customer_id', $customer->id);
        $usersTotal  = (clone $integratedUsers)->count();
        $usersActive = (clone $integratedUsers)->where('last_activity', '>=', now()->subDays(30))->count();

        $activeRooms = BingoRoom::where('creator_user_id', $userId)->active()->count();
        $totalRooms  = BingoRoom::where('creator_user_id', $userId)->count();

        return [
            'role'          => 'customer',
            'id'            => $customer->hashed_id,
            'name'          => $customer->name,
            'business_name' => $customer->business_name,
            'phone'         => $customer->phone,
            'status'        => $customer->status,
            'country'       => $customer->country?->name,
            'currency'      => $customer->currency ? "{$customer->currency->name} ({$customer->currency->symbol})" : null,
            'user'          => ['username' => $customer->user->username, 'email' => $customer->user->email],
            'commission'    => (float) $customer->commission,
            'currencies'    => $currencies,
            'games'         => $customer->allowedGames()->pluck('name')->unique()->values()->all(),

            'hierarchy' => [
                'chain' => [['level' => 'customer', 'name' => $customer->name, 'rate' => (float) $customer->commission, 'status' => $customer->status, 'is_self' => true]],
            ],

            // Integración API: la clave nunca viaja completa (solo los últimos
            // 4 caracteres) — se gestiona/rota desde administración.
            'integration' => [
                'api_route'      => $customer->api_route,
                'key_hint'       => $customer->customer_key ? '••••' . substr($customer->customer_key, -4) : null,
                'users_total'    => $usersTotal,
                'users_active'   => $usersActive,
                'rooms_active'   => $activeRooms,
                'rooms_total'    => $totalRooms,
            ],

            'performance'      => $this->insights->performance($lotScope, self::ROOM_COMMISSION_SQL),
            'alerts'           => $this->alerts($customer, $totalRooms),
            'security'         => $this->insights->security($userId),
            'wallets'          => $this->enrichment->wallets($customer->user),
            'recent_movements' => $this->enrichment->recentMovements($customer->user),
            'account_meta'     => $this->enrichment->accountMeta($userId),
        ];
    }

    /**
     * Solo nombre y teléfono — comisión, integración API, monedas y juegos se
     * gestionan desde administración.
     *
     * @param array{name?: string, phone?: string} $data
     */
    public function updateOwn(array $data, ?string $targetUserId = null): void
    {
        $customer = $this->ownCustomerOrFail($targetUserId);
        $customer->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }

    private function alerts(Customer $customer, int $totalRooms): array
    {
        $alerts = [];

        if ($customer->status !== 'ACTIVE') {
            $alerts[] = Insights::alert('danger', 'fa-ban', 'Cuenta de cliente no activa',
                'Tu cuenta está ' . strtolower((string) $customer->status) . ': la integración API y tus salas quedan suspendidas. Contacta a soporte.');
        }

        if (!$customer->api_route || !$customer->customer_key) {
            $alerts[] = Insights::alert('warning', 'fa-plug-circle-exclamation', 'Integración API incompleta',
                'Falta configurar el endpoint o la clave de tu integración. Pídele a soporte que la complete para recibir usuarios por API.');
        }

        if ($totalRooms === 0) {
            $alerts[] = Insights::alert('info', 'fa-dice', 'Aún no tienes salas',
                'Crea tu primera sala de bingo para empezar a generar comisión.', 'Crear sala', '/bingo-rooms');
        }

        return $alerts;
    }
}
