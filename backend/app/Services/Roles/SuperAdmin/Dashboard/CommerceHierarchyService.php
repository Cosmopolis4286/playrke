<?php

namespace App\Services\Roles\SuperAdmin\Dashboard;

use App\Models\Roles\Commerce;
use Illuminate\Support\Facades\Cache;

/**
 * Jerarquía real Comercializadora → Agencia → Vendedor, para SuperAdmin —
 * "Entidades por rol" cuenta comercios/vendedores como bloques planos
 * independientes; este panel muestra la relación real entre ellos
 * (Commerce::sellers()/subsidiaries(), ya existentes).
 */
final class CommerceHierarchyService
{
    private const CACHE_KEY = 'superadmin:commerce-hierarchy';
    private const CACHE_TTL = 90; // mismo TTL que el resto de agregados del dashboard superadmin

    public function getHierarchy() : array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildHierarchy());
    }

    private function buildHierarchy() : array
    {
        $commerces = Commerce::query()
            ->withCount(['sellers', 'subsidiaries'])
            ->with(['subsidiaries' => fn ($q) => $q->withCount('sellers')])
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'name', 'status']);

        $rows = $commerces->map(function(Commerce $commerce) {
            $subsidiarySellers = $commerce->subsidiaries->sum('sellers_count');

            return [
                'commerce_id' => (string) $commerce->id,
                'commerce_name' => $commerce->name,
                'status' => $commerce->status,
                'direct_sellers' => $commerce->sellers_count,
                'subsidiaries_count' => $commerce->subsidiaries_count,
                'subsidiary_sellers' => $subsidiarySellers,
                'total_sellers' => $commerce->sellers_count + $subsidiarySellers,
            ];
        })->values();

        return [
            'commerces' => $rows,
            'totals' => [
                'commerce_count' => Commerce::count(),
                'total_sellers' => $rows->sum('total_sellers'),
                'total_subsidiaries' => $rows->sum('subsidiaries_count'),
            ],
        ];
    }
}
