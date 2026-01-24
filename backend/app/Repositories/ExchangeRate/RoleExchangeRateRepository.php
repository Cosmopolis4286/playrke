<?php

namespace App\Repositories\ExchangeRate;

use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Repositories\BaseRepository;
use App\Repositories\ExchangeRateRepository;
use App\Traits\ExchangeRate\ResolvesExchangeRateForeignKey;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositorio para la gestión de tipos de cambio.
 *
 * Implementa las operaciones específicas para el modelo ExchangeRate,
 * incluyendo gestión de valores, conversiones y estadísticas.
 */
class RoleExchangeRateRepository extends ExchangeRateRepository
{
    use ResolvesExchangeRateForeignKey;

    public function createOrRestoreByCurrency(
        int $roleEntityId,
        ExchangeRateDTO $dto
    ): Model {
        $data = $dto->toArray();

        $model = $this->model
            ->withTrashed()
            ->where('currency_id', $dto->currencyId)
            ->where(
                fn($q) =>
                $q->where('commerce_id', $roleEntityId)
                    ->orWhere('customer_id', $roleEntityId)
            )
            ->first();

        if ($model) {
            $model->trashed() && $model->restore();
            $model->update($data);
            return $model;
        }

        return $this->model->create(
            $data + $this->foreignKeyData($roleEntityId)
        );
    }
}
