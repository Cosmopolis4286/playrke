<?php

namespace App\Http\Resources\Roles\Seller\Manager;

use Illuminate\Http\Resources\Json\JsonResource;

class ManagerSellerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->hashed_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'commission' => $this->commission,

            'country' => [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ],

            'user' => [
                'username' => $this->user->username,
                'email' => $this->user->email,
                'time_zone_id' => $this->user->timeZone->id ?? null,
            ],

            // Moneda principal (nivel 1) - debe devolver ID único, no colección
            'currency' => $this->currenciesActive
                ->where('pivot.level', 1)
                ->pluck('id')
                ->first(), // solo la principal

            // Otras monedas (nivel 2) - array plano de IDs
            'other_currencies' => $this->currenciesActive
                ->where('pivot.level', 2)
                ->pluck('id')
                ->values()
                ->all(),

            'allowed_games' => $this->whenLoaded('gamesActive', function () {
                return $this->gamesActive
                    ->pluck('id')
                    ->values()
                    ->all();
            }),

            // Manager actual (Commerce o Subsidiary) — precarga el select
            // "Manager" del formulario en modo edición (solo lo usa admin,
            // ver ManagerSellerForm.vue). manager_user_id ya es siempre el
            // user_id de un Commerce (Seller::resolveCommerce()); cuando hay
            // Subsidiary intermedia, se expone su propio user_id en su lugar
            // para que el select preseleccione la opción "(Agencia)" real, no
            // el Commerce raíz.
            'manager_user_id' => $this->subsidiary?->user_id ?? $this->manager_user_id,
        ];
    }
}
