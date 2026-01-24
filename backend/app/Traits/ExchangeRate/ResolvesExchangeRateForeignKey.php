<?php

namespace App\Traits\ExchangeRate;

use Illuminate\Database\Eloquent\Model;

trait ResolvesExchangeRateForeignKey
{
    /**
     * Devuelve la foreign key dinámica según el modelo del repositorio.
     *
     * @param int $roleEntityId
     * @return array
     *
     * @throws \LogicException
     */
    public function foreignKeyData(int $roleEntityId): array
    {
        /** @var Model $model */
        $model = $this->model;

        return match ($model->getTable()) {
            'commerces_exchange_rates' => [
                'commerce_id' => $roleEntityId,
            ],
            'customers_exchange_rates' => [
                'customer_id' => $roleEntityId,
            ],
            default => throw new \LogicException(
                "Modelo no soportado para foreign key dinámica: {$model->getTable()}"
            ),
        };
    }
}
