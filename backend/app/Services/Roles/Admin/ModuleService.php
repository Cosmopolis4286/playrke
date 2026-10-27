<?php

namespace App\Services\Roles\Admin;

use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;

final class ModuleService
{
    public function getAll() : Collection
    {
        return Module::orderBy('label')->get();
    }

    public function toggleStatus(string $moduleId) : Module
    {
        $module = Module::findOrFail($moduleId);
        $module->update(['is_active' => ! $module->is_active]);

        return $module;
    }
}
