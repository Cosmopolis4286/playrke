<?php

namespace App\Http\Controllers;

use App\Models\AppParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AppParameterController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AppParameter::class);

        return Inertia::render('AppParameters/Index', [
            'parameters'      => AppParameter::orderBy('param')->get(),
            'roleUser'        => Auth::user()?->roles?->first()?->prefix ?? 'guest',
            'critical_params' => AppParameter::CRITICAL_PARAMS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', AppParameter::class);

        $validated = $request->validate([
            'param'  => 'required|string|max:50|unique:app_parameters,param',
            'value'  => 'nullable|string|max:100',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $validated['param'] = strtolower(trim($validated['param']));

        $parameter = AppParameter::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Parámetro creado exitosamente',
            'data'    => $parameter,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $parameter = AppParameter::findOrFail($id);
        $this->authorize('view', $parameter);

        return response()->json([
            'success' => true,
            'data'    => $parameter,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $parameter = AppParameter::findOrFail($id);
        $this->authorize('update', $parameter);

        $validated = $request->validate([
            'param'  => 'required|string|max:50|unique:app_parameters,param,' . $id,
            'value'  => 'nullable|string|max:100',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $validated['param'] = strtolower(trim($validated['param']));

        $parameter->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Parámetro actualizado exitosamente',
            'data'    => $parameter->fresh(),
        ]);
    }

    public function delete(string $id): JsonResponse
    {
        $parameter = AppParameter::findOrFail($id);
        $this->authorize('delete', $parameter);

        if ($parameter->isCriticalSystemParam()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un parámetro crítico del sistema',
            ], 422);
        }

        $parameter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Parámetro eliminado exitosamente',
        ]);
    }

    public function fetchList(Request $request): JsonResponse
    {
        $this->authorize('fetchList', AppParameter::class);

        $parameters = AppParameter::when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('param'), fn($q, $p) => $q->byParam($p))
            ->orderBy('param')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $parameters,
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $parameter = AppParameter::findOrFail($id);
        $this->authorize('update', $parameter);

        $next = $parameter->status === AppParameter::STATUS_ACTIVE
            ? AppParameter::STATUS_INACTIVE
            : AppParameter::STATUS_ACTIVE;

        $parameter->update(['status' => $next]);

        return response()->json([
            'success' => true,
            'message' => 'Estado del parámetro actualizado',
            'data'    => $parameter->fresh(),
        ]);
    }
}
