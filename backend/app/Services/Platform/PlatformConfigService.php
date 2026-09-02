<?php

namespace App\Services\Platform;

use App\Models\PlatformConfig;
use App\Models\Roles\Commerce;

/**
 * Servicio de aplicación sobre la configuración global de la plataforma
 * (tabla singleton platform_config).
 *
 * Vive en un namespace neutral (no Roles/Admin/Commerce) porque no es "una
 * operación sobre Commerce" sino "config global que referencia a Commerce" —
 * queda como punto de extensión limpio para cuando otros dominios (ej. rooms
 * sin comercio asociado) necesiten resolver "el comercio por defecto".
 */
final class PlatformConfigService
{
    /**
     * Fila singleton de configuración. La migración de creación garantiza
     * que exista exactamente una fila.
     */
    private function config(): PlatformConfig
    {
        return PlatformConfig::query()->firstOrFail();
    }

    public function getDefaultCommerceId(): ?string
    {
        return $this->config()->default_commerce_id;
    }

    public function getDefaultCommerce(): ?Commerce
    {
        return $this->config()->defaultCommerce()->first();
    }

    public function isDefaultCommerce(string $commerceId): bool
    {
        return $this->getDefaultCommerceId() === $commerceId;
    }

    public function setDefaultCommerce(Commerce $commerce): void
    {
        $this->config()->update(['default_commerce_id' => $commerce->id]);
    }
}
