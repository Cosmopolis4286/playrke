<?php

namespace App\Repositories\ExchangeRate;

use App\Contracts\ExchangeRate\RoleExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Repositories\BaseRepository;
use App\Traits\ExchangeRate\ResolvesExchangeRateForeignKey;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositorio para la gestión de tipos de cambio.
 *
 * Implementa las operaciones específicas para el modelo ExchangeRate,
 * incluyendo gestión de valores, conversiones y estadísticas.
 */
abstract class RoleExchangeRateRepository extends BaseRepository implements RoleExchangeRateRepositoryInterface
{
    use ResolvesExchangeRateForeignKey;

    public function createOrRestoreByCurrency(int $roleEntityId, ExchangeRateDTO $dto): Model
    {
        $data = $dto->toArray();

        $foreignKeyData = $this->foreignKeyData($roleEntityId);
        $foreignKeyColumn = array_key_first($foreignKeyData);

        $model = $this->model
            ->withTrashed()
            ->where('currency_id', $dto->currencyId)
            ->where($foreignKeyColumn, $roleEntityId)
            ->first();

        if ($model) {
            $model->trashed() && $model->restore();
            $model->update($data);
            return $model;
        }

        return $this->model->create($data + $foreignKeyData);
    }
}
