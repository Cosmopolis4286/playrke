<?php

namespace App\Services\App;

class VersionManagerService
{
    protected string $currentVersion;

    public function __construct()
    {
        $this->currentVersion = config('app.version', '0.0.0');
    }

    /**
     * Devuelve la versión actual
     */
    public function getCurrentVersion() : string
    {
        return $this->currentVersion;
    }

    /**
     * Devuelve el tipo de actualización: major, minor, patch
     */
    public function getUpdateType(string $recommendedVersion) : ?string
    {
        [$currMajor, $currMinor, $currPatch] = $this->parseVersion($this->currentVersion);
        [$recMajor, $recMinor, $recPatch] = $this->parseVersion($recommendedVersion);

        if ($recMajor > $currMajor) {
            return 'major';
        }
        if ($recMinor > $currMinor) {
            return 'minor';
        }
        if ($recPatch > $currPatch) {
            return 'patch';
        }

        return null; // No hay actualización
    }

    /**
     * Compara la versión actual con la recomendada y devuelve true si se recomienda actualizar
     */
    public function isUpdateRecommended(string $recommendedVersion) : bool
    {
        return version_compare($this->currentVersion, $recommendedVersion, '<');
    }

    protected function parseVersion(string $version) : array
    {
        $parts = explode('.', $version);

        return [
            intval($parts[0] ?? 0),
            intval($parts[1] ?? 0),
            intval($parts[2] ?? 0),
        ];
    }
}
