<?php

namespace App\Http\Controllers;

use App\Models\MovementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MovementTypeController extends Controller
{
    /**
     * Display a listing of the movement types.
     */
    public function index(Request $request): Response
    {
        $movementTypes = MovementType::orderBy('label')->get();

        return Inertia::render('MovementTypes/Index', [
            'movementTypes' => $movementTypes,
            'tokenId'       => $request->get('token'),
        ]);
    }

    /**
     * Store a newly created movement type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'              => 'required|string|max:30|regex:/^[A-Z_]+$/|unique:movement_types,code',
            'label'             => 'required|string|max:100',
            'description'       => 'nullable|string|max:255',
            'icon'              => 'nullable|string|max:60',
            'color'             => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_credit'         => 'required|boolean',
            'requires_approval' => 'required|boolean',
            'status'            => 'required|in:ACTIVE,INACTIVE',
        ]);

        $movementType = MovementType::create($validated + ['is_system' => false]);

        MovementType::forgetCatalogCache();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de movimiento creado exitosamente',
            'data'    => $movementType,
        ]);
    }

    /**
     * Display the specified movement type.
     */
    public function show(string $id): JsonResponse
    {
        $movementType = MovementType::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $movementType,
        ]);
    }

    /**
     * Update the specified movement type.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $movementType = MovementType::findOrFail($id);

        $codeRule = $movementType->is_system || $movementType->movements()->exists()
            ? 'prohibited'
            : 'required|string|max:30|regex:/^[A-Z_]+$/|unique:movement_types,code,' . $id;

        $validated = $request->validate([
            'code'              => $codeRule,
            'label'             => 'required|string|max:100',
            'description'       => 'nullable|string|max:255',
            'icon'              => 'nullable|string|max:60',
            'color'             => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_credit'         => 'required|boolean',
            'requires_approval' => 'required|boolean',
            'status'            => 'required|in:ACTIVE,INACTIVE',
        ]);

        $movementType->update($validated);

        MovementType::forgetCatalogCache();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de movimiento actualizado exitosamente',
            'data'    => $movementType->fresh(),
        ]);
    }

    /**
     * Delete the specified movement type.
     */
    public function delete(string $id): JsonResponse
    {
        $movementType = MovementType::findOrFail($id);

        if ($movementType->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un tipo de movimiento del sistema',
            ], 422);
        }

        if ($movementType->movements()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el tipo de movimiento porque tiene movimientos asociados',
            ], 422);
        }

        $movementType->delete();

        MovementType::forgetCatalogCache();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de movimiento eliminado exitosamente',
        ]);
    }

    /**
     * Fetch list of movement types for AJAX requests.
     */
    public function fetchList(Request $request): JsonResponse
    {
        $movementTypes = MovementType::when($request->get('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('label')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $movementTypes,
        ]);
    }

    /**
     * Toggle movement type status.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $movementType = MovementType::findOrFail($id);

        $newStatus = $movementType->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $movementType->update(['status' => $newStatus]);

        MovementType::forgetCatalogCache();

        return response()->json([
            'success' => true,
            'message' => 'Estado del tipo de movimiento actualizado exitosamente',
            'data'    => $movementType->fresh(),
        ]);
    }
}
