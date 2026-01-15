<?php

namespace App\Repositories\Games\Shared\GameRoom;

use App\Models\Shared\GameRoom\GameRoomJackpot;
use App\Repositories\BaseRepository;
use App\Repositories\Games\Shared\GameRoom\Contracts\GameRoomJackpotRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

class GameRoomJackpotRepository extends BaseRepository implements GameRoomJackpotRepositoryContract
{
    public function __construct(GameRoomJackpot $model)
    {
        parent::__construct($model);
    }

    public function getByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    public function getAvailableJackpots(): Collection
    {
        return $this->model->where('status', 'AVAILABLE')->get();
    }

    public function getUsedJackpots(): Collection
    {
        return $this->model->where('status', 'USED')->get();
    }

    public function getJackpotsByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    public function getJackpotsByAmountRange(float $minAmount, float $maxAmount): Collection
    {
        return $this->model->whereBetween('amount', [$minAmount, $maxAmount])->get();
    }

    public function getAvailableJackpotsByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)
            ->where('status', 'AVAILABLE')
            ->get();
    }

    public function getMaxJackpotByCurrency($currencyId): ?GameRoomJackpot
    {
        return $this->model->where('currency_id', $currencyId)
            ->where('status', 'AVAILABLE')
            ->orderBy('amount', 'desc')
            ->first();
    }

    public function getMinJackpotByCurrency($currencyId): ?GameRoomJackpot
    {
        return $this->model->where('currency_id', $currencyId)
            ->where('status', 'AVAILABLE')
            ->orderBy('amount', 'asc')
            ->first();
    }

    public function getTotalJackpotAmountByCurrency($currencyId): float
    {
        return $this->model->where('currency_id', $currencyId)
            ->where('status', 'AVAILABLE')
            ->sum('amount');
    }

    public function getTotalAvailableJackpotAmount(): float
    {
        return $this->model->where('status', 'AVAILABLE')->sum('amount');
    }

    public function createJackpot($currencyId, float $amount, string $status = 'AVAILABLE'): GameRoomJackpot
    {
        return $this->model->create([
            'currency_id' => $currencyId,
            'amount' => $amount,
            'status' => $status
        ]);
    }

    public function markAsUsed($jackpotId): bool
    {
        return $this->model->where('id', $jackpotId)
            ->update(['status' => 'USED']) > 0;
    }

    public function markAsAvailable($jackpotId): bool
    {
        return $this->model->where('id', $jackpotId)
            ->update(['status' => 'AVAILABLE']) > 0;
    }

    public function updateAmount($jackpotId, float $amount): bool
    {
        return $this->model->where('id', $jackpotId)
            ->update(['amount' => $amount]) > 0;
    }

    public function getJackpotStatistics(): array
    {
        $total = $this->model->count();
        $available = $this->model->where('status', 'AVAILABLE')->count();
        $used = $this->model->where('status', 'USED')->count();
        $totalAmount = $this->model->sum('amount');
        $availableAmount = $this->model->where('status', 'AVAILABLE')->sum('amount');

        return [
            'total_jackpots' => $total,
            'available_jackpots' => $available,
            'used_jackpots' => $used,
            'total_amount' => $totalAmount,
            'available_amount' => $availableAmount,
            'used_amount' => $totalAmount - $availableAmount,
            'availability_percentage' => $total > 0 ? round(($available / $total) * 100, 2) : 0
        ];
    }

    public function getCurrencyStatistics($currencyId): array
    {
        $total = $this->model->where('currency_id', $currencyId)->count();
        $available = $this->model->where('currency_id', $currencyId)
            ->where('status', 'AVAILABLE')->count();
        $totalAmount = $this->model->where('currency_id', $currencyId)->sum('amount');
        $availableAmount = $this->model->where('currency_id', $currencyId)
            ->where('status', 'AVAILABLE')->sum('amount');
        $maxAmount = $this->model->where('currency_id', $currencyId)
            ->where('status', 'AVAILABLE')->max('amount');
        $minAmount = $this->model->where('currency_id', $currencyId)
            ->where('status', 'AVAILABLE')->min('amount');

        return [
            'total_jackpots' => $total,
            'available_jackpots' => $available,
            'total_amount' => $totalAmount,
            'available_amount' => $availableAmount,
            'max_amount' => $maxAmount ?? 0,
            'min_amount' => $minAmount ?? 0,
            'average_amount' => $available > 0 ? round($availableAmount / $available, 2) : 0
        ];
    }

    public function bulkUpdateStatus(array $jackpotIds, string $status): int
    {
        return $this->model->whereIn('id', $jackpotIds)
            ->update(['status' => $status]);
    }

    public function deleteJackpot($jackpotId): bool
    {
        return $this->model->where('id', $jackpotId)->delete() > 0;
    }

    public function getJackpotsAboveAmount(float $amount): Collection
    {
        return $this->model->where('amount', '>', $amount)
            ->where('status', 'AVAILABLE')
            ->orderBy('amount', 'desc')
            ->get();
    }

    public function getJackpotsBelowAmount(float $amount): Collection
    {
        return $this->model->where('amount', '<', $amount)
            ->where('status', 'AVAILABLE')
            ->orderBy('amount', 'asc')
            ->get();
    }

    public function getRecentJackpots(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTopJackpotsByCurrency($currencyId, int $limit = 10): Collection
    {
        return $this->model->where('currency_id', $currencyId)
            ->where('status', 'AVAILABLE')
            ->orderBy('amount', 'desc')
            ->limit($limit)
            ->get();
    }
}