<?php

namespace App\Services;

use App\Models\TosVersion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class TosService
{
    private const CACHE_KEY = 'tos:current_version_id';
    private const CACHE_TTL = 3600; // 1 hora

    public function currentVersion(): ?TosVersion
    {
        $id = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => TosVersion::current()?->id);

        return $id ? TosVersion::find($id) : null;
    }

    public function currentVersionString(): ?string
    {
        return $this->currentVersion()?->version;
    }

    /**
     * Registra la aceptación explícita del usuario con trazabilidad legal:
     * versión, timestamp e IP.
     */
    public function accept(User $user, string $ip): void
    {
        $current = $this->currentVersion();

        if (! $current) {
            return;
        }

        $user->update([
            'tos_accepted_version' => $current->version,
            'tos_accepted_at'      => now(),
            'tos_accepted_ip'      => $ip,
        ]);
    }

    /**
     * ¿El usuario necesita aceptar (o re-aceptar) los TOS?
     */
    public function needsAcceptance(User $user): bool
    {
        $current = $this->currentVersionString();
        return $user->tosNeedsRenewal($current);
    }

    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY); // 'tos:current_version_id'
    }
}
