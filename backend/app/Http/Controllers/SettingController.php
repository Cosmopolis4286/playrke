<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Setting::class);

        return Inertia::render('Settings/Index', [
            'settings' => Setting::orderBy('param')->get(),
            'roleUser' => Auth::user()?->roles?->first()?->prefix ?? 'guest',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Setting::class);

        $validated = $request->validate([
            'param'  => 'required|string|max:50|unique:settings,param',
            'value'  => 'nullable|string|max:100',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $validated['param'] = strtolower(trim($validated['param']));

        $setting = Setting::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Parámetro creado exitosamente',
            'data'    => $setting,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $setting = Setting::findOrFail($id);
        $this->authorize('view', $setting);

        return response()->json([
            'success' => true,
            'data'    => $setting,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $setting = Setting::findOrFail($id);
        $this->authorize('update', $setting);

        $validated = $request->validate([
            'param'  => 'required|string|max:50|unique:settings,param,' . $id,
            'value'  => 'nullable|string|max:100',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $validated['param'] = strtolower(trim($validated['param']));

        $setting->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Parámetro actualizado exitosamente',
            'data'    => $setting->fresh(),
        ]);
    }

    public function delete(string $id): JsonResponse
    {
        $setting = Setting::findOrFail($id);
        $this->authorize('delete', $setting);

        if ($setting->isCriticalSystemParam()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un parámetro crítico del sistema',
            ], 422);
        }

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Parámetro eliminado exitosamente',
        ]);
    }

    public function fetchList(Request $request): JsonResponse
    {
        $this->authorize('fetchList', Setting::class);

        $settings = Setting::when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('param'), fn($q, $p) => $q->byParam($p))
            ->orderBy('param')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $settings,
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $setting = Setting::findOrFail($id);
        $this->authorize('update', $setting);

        $next = $setting->status === Setting::STATUS_ACTIVE
            ? Setting::STATUS_INACTIVE
            : Setting::STATUS_ACTIVE;

        $setting->update(['status' => $next]);

        return response()->json([
            'success' => true,
            'message' => 'Estado del parámetro actualizado',
            'data'    => $setting->fresh(),
        ]);
    }
}
