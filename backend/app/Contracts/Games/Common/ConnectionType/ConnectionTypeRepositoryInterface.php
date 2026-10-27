<?php

namespace App\Contracts\Games\Common\ConnectionType;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Common\ConnectionType\ConnectionType;
use Illuminate\Database\Eloquent\Collection;

interface ConnectionTypeRepositoryInterface extends BaseRepositoryInterface
{
    public function activateConnectionType($connectionTypeId) : bool;

    public function bulkActivateConnectionTypes(array $connectionTypeIds) : int;

    public function bulkDeactivateConnectionTypes(array $connectionTypeIds) : int;

    public function createConnectionType(string $code, string $label, bool $isActive = true) : ConnectionType;

    public function deactivateConnectionType($connectionTypeId) : bool;

    public function existsByLabel(string $label) : bool;

    public function getActiveConnectionTypes() : Collection;

    public function getByLabel(string $label) : ?ConnectionType;

    public function getConnectionTypesByStatus(bool $isActive = true) : Collection;

    public function getConnectionTypeStatistics() : array;

    public function getInactiveConnectionTypes() : Collection;

    public function getRecentlyCreatedConnectionTypes(int $days = 30) : Collection;

    public function searchByLabel(string $searchTerm) : Collection;

    public function toggleConnectionTypeStatus($connectionTypeId) : bool;

    public function updateConnectionType($connectionTypeId, array $data) : bool;
}
