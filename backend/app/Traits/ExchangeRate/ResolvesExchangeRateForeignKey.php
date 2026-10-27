<?php

namespace App\Traits\ExchangeRate;

use Illuminate\Database\Eloquent\Model;
use LogicException;

trait ResolvesExchangeRateForeignKey
{
    /**
     * Devuelve la foreign key dinámica según el modelo recibido.
     *
     * @param Model $model Modelo Eloquent para resolver tabla
     *
     * @throws LogicException
     */
    public function foreignKeyData(int $roleEntityId, Model $model) : array
    {
        return match ($model->getTable()) {
            'commerces_exchange_rates' => [
                'commerce_id' => $roleEntityId,
            ],
            'customers_exchange_rates' => [
                'customer_id' => $roleEntityId,
            ],
            default => throw new LogicException(
                "Modelo no soportado para foreign key dinámica: {$model->getTable()}",
            ),
        };
    }
}
