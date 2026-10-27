<?php

namespace App\Http\Requests\Content;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;
use Illuminate\Validation\Rule;

class LandingSectionReorderRequest extends BaseRequest
{
    public function authorize() : bool
    {
        return PermissionService::checkPrefix(['superadmin', 'admin']);
    }

    public function rules() : array
    {
        return [
            'ordered_keys' => ['required', 'array'],
            'ordered_keys.*' => ['required', 'string', Rule::exists('landing_sections', 'section_key')],
        ];
    }
}
