<?php

namespace App\Http\Requests\Roles\Admin\ExternalPlayer;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros de búsqueda del panel admin de Jugadores Externos.
 * authorize() delega en ExternalPlayerPolicy vía $this->authorize() del
 * controller — este Request solo valida forma, no autoriza acceso.
 */
class ExternalPlayerSearchRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'status'   => ['sometimes', 'nullable', Rule::in(['ACTIVE', 'INACTIVE', 'BLOCKED'])],
            'game_id'  => ['sometimes', 'nullable', 'string', 'exists:games,id'],
            'country_id' => ['sometimes', 'nullable', 'string', 'exists:countries,id'],
            'seller_user_id' => ['sometimes', 'nullable', 'string', 'exists:users,id'],
            'seller_search'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'commerce_id'    => ['sometimes', 'nullable', 'string', 'exists:commerces,id'],
            'subsidiary_id'  => ['sometimes', 'nullable', 'string', 'exists:subsidiaries,id'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        return [
            'search'         => $this->validated('search') ?: null,
            'status'         => $this->validated('status') ?: null,
            'game_id'        => $this->validated('game_id') ?: null,
            'country_id'     => $this->validated('country_id') ?: null,
            'seller_user_id' => $this->validated('seller_user_id') ?: null,
            'seller_search'  => $this->validated('seller_search') ?: null,
            'commerce_id'    => $this->validated('commerce_id') ?: null,
            'subsidiary_id'  => $this->validated('subsidiary_id') ?: null,
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?: 20);
    }
}
