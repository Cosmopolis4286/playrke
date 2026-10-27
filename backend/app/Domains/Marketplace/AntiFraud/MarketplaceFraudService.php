<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\AntiFraud;

use App\Domains\Marketplace\Models\Order;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Detección de patrones sospechosos específicos de P2P trading — compone
 * WalletFraudService (velocity de depósito/retiro, ya existente), no lo
 * reemplaza. Ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 25.
 *
 * Todos los métodos lanzan DomainException si detectan un patrón bloqueante
 * (self-trading), o retornan un score/booleano para patrones que deben
 * señalizarse pero no bloquear automáticamente (wash trading, precio
 * anómalo, velocity) — el criterio de bloqueo automático vs. señal para
 * revisión manual sigue el mismo patrón que ReputationService: computar
 * sobre datos ya existentes, sin inventar un segundo sistema de decisión.
 */
final class MarketplaceFraudService
{
    /** Ventana en la que una recompra del mismo activo entre las mismas dos partes se considera circular. */
    private const CIRCULAR_TRADE_WINDOW_HOURS = 24;

    /** Ventana anti double-submit al comprar (segundos) — mismo mecanismo que WalletFraudService::checkDoubleSubmit(). */
    private const DOUBLE_SUBMIT_WINDOW_SECONDS = 10;

    /** Máximo de listings nuevos por usuario por día antes de señalizar. */
    private const MAX_LISTINGS_PER_DAY = 50;

    /**
     * Previene double-submit al comprar: mismo comprador + mismo listing en
     * una ventana corta — protege contra doble click generando dos Order
     * antes de que el primer buy() reserve el listing (la reserva atómica
     * en OrderService ya lo previene a nivel de datos; esto evita el
     * intento redundante y su mensaje de error confuso al usuario).
     */
    public function checkDoubleSubmit(string $buyerId, string $listingId) : void
    {
        $key = "marketplace_fraud:ds:{$buyerId}:{$listingId}";

        if (Cache::has($key)) {
            throw new DomainException('Solicitud duplicada. Espera unos segundos antes de reintentar.');
        }

        Cache::put($key, true, self::DOUBLE_SUBMIT_WINDOW_SECONDS);
    }

    /**
     * Velocity de listings nuevos por vendedor en el día — mismo mecanismo
     * de Cache que WalletFraudService::checkDoubleSubmit()/operationCountToday(),
     * sin duplicar su implementación (esta clase compone, no hereda).
     */
    public function checkListingVelocity(string $sellerId) : void
    {
        $cacheKey = "marketplace_fraud:listings:{$sellerId}:" . now()->format('Y-m-d');
        $ttl = Carbon::now()->secondsUntilEndOfDay() + 1;

        $count = (int) Cache::remember($cacheKey, $ttl, function() use ($sellerId) {
            return DB::table('marketplace_listings')
                ->where('seller_id', $sellerId)
                ->where('created_at', '>=', Carbon::today())
                ->count();
        });

        if ($count >= self::MAX_LISTINGS_PER_DAY) {
            throw new DomainException('Has alcanzado el límite de publicaciones por hoy. Inténtalo mañana.');
        }
    }

    /**
     * Wash trading / operación circular: A vende a B, y B revende el mismo
     * PlayerAsset (rastreado por asset_definition_id, ya que el ID de
     * instancia cambia en cada transferencia) de vuelta a A dentro de la
     * ventana. Señal, no bloqueo automático — un patrón legítimo (el
     * comprador se arrepiente y revende) es indistinguible de wash trading
     * sin más contexto; se expone para revisión de admin.
     */
    public function detectCircularTrade(Order $order) : bool
    {
        $listing = $order->listing;
        $assetDefinitionId = $listing->playerAsset->asset_definition_id;

        return Order::query()
            ->join('marketplace_listings', 'marketplace_listings.id', '=', 'marketplace_orders.listing_id')
            ->join('player_assets', 'player_assets.id', '=', 'marketplace_listings.player_asset_id')
            ->where('player_assets.asset_definition_id', $assetDefinitionId)
            ->where('marketplace_listings.seller_id', (string) $order->buyer_id)
            ->where('marketplace_orders.buyer_id', (string) $listing->seller_id)
            ->where('marketplace_orders.status', Order::STATUS_COMPLETED)
            ->where('marketplace_orders.created_at', '>=', Carbon::now()->subHours(self::CIRCULAR_TRADE_WINDOW_HOURS))
            ->exists();
    }

    /**
     * Precio anómalo: desviación mayor a $thresholdPercent respecto a la
     * mediana de las últimas ventas completadas del mismo tipo de activo.
     * Sin ventas previas no hay base de comparación — nunca bloquea al
     * primer vendedor de un activo nuevo.
     */
    public function isAnomalousPrice(string $assetDefinitionId, float $price, float $thresholdPercent = 300.0) : bool
    {
        $recentPrices = Order::query()
            ->join('marketplace_listings', 'marketplace_listings.id', '=', 'marketplace_orders.listing_id')
            ->join('player_assets', 'player_assets.id', '=', 'marketplace_listings.player_asset_id')
            ->where('player_assets.asset_definition_id', $assetDefinitionId)
            ->where('marketplace_orders.status', Order::STATUS_COMPLETED)
            ->orderByDesc('marketplace_orders.created_at')
            ->limit(20)
            ->pluck('marketplace_orders.price')
            ->map(fn ($p) => (float) $p)
            ->sort()
            ->values();

        if ($recentPrices->count() < 3) {
            return false;
        }

        $median = $recentPrices[intdiv($recentPrices->count(), 2)];
        if ($median <= 0) {
            return false;
        }

        $deviationPercent = abs($price - $median) / $median * 100;

        return $deviationPercent > $thresholdPercent;
    }

    /**
     * Self-trading: comprador y vendedor son el mismo usuario. Ya bloqueado
     * en OrderService::buy() por comparación directa de IDs — este método
     * cubre el caso de cuentas relacionadas por evidencia indirecta (mismo
     * método de pago, ver nota abajo). Sin infraestructura de verificación
     * de identidad/KYC-linking hoy, la única señal disponible y confiable
     * es el ID de usuario — devuelve false explícitamente en vez de
     * inventar heurísticas sin datos reales que las respalden.
     */
    public function isSelfTrade(string $buyerId, string $sellerId) : bool
    {
        return $buyerId === $sellerId;
    }
}
