<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Controlador de perfil de usuario.
 * 
 * Maneja todas las operaciones relacionadas con el perfil del usuario:
 * - Visualización y edición de perfil
 * - Gestión de avatar
 * - Cambio de contraseña
 * - Preferencias de usuario
 * - Estadísticas del jugador
 */
final class UserController extends Controller
{
    /**
     * Obtener perfil completo del usuario.
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user()->load(['roles', 'wallet']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date,
                'country' => $user->country,
                'bio' => $user->bio,
                'is_verified' => $user->is_verified,
                'created_at' => $user->created_at,
                'roles' => $user->roles->pluck('name'),
                'wallet' => $user->wallet ? [
                    'balance' => $user->wallet->balance,
                    'currency' => $user->wallet->currency,
                ] : null,
            ]
        ]);
    }

    /**
     * Actualizar información del perfil.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
                'regex:/^[a-zA-Z0-9_]+$/'
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'country' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only([
            'name', 'username', 'email', 'phone', 'birth_date', 'country', 'bio'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Actualizar contraseña.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    /**
     * Subir avatar.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Eliminar avatar anterior si existe
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully',
            'data' => [
                'avatar' => Storage::url($path)
            ]
        ]);
    }

    /**
     * Eliminar avatar.
     */
    public function deleteAvatar(): JsonResponse
    {
        $user = Auth::user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar deleted successfully'
        ]);
    }

    /**
     * Obtener preferencias del usuario.
     */
    public function getPreferences(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'language' => $user->preferences['language'] ?? 'en',
                'timezone' => $user->preferences['timezone'] ?? 'UTC',
                'notifications' => $user->preferences['notifications'] ?? [
                    'email' => true,
                    'push' => true,
                    'sms' => false,
                ],
                'privacy' => $user->preferences['privacy'] ?? [
                    'profile_visible' => true,
                    'show_email' => false,
                    'show_phone' => false,
                ],
                'game_settings' => $user->preferences['game_settings'] ?? [
                    'sound_enabled' => true,
                    'music_enabled' => true,
                    'auto_dauber' => false,
                    'card_animation' => true,
                ],
            ]
        ]);
    }

    /**
     * Actualizar preferencias del usuario.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'notifications' => 'nullable|array',
            'privacy' => 'nullable|array',
            'game_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        $preferences = array_merge($user->preferences ?? [], $request->only([
            'language', 'timezone', 'notifications', 'privacy', 'game_settings'
        ]));

        $user->update(['preferences' => $preferences]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data' => $preferences
        ]);
    }

    /**
     * Obtener estadísticas del jugador.
     */
    public function getStats(): JsonResponse
    {
        $user = Auth::user();

        // Aquí deberías obtener las estadísticas reales de tu base de datos
        // Por ahora, devolvemos datos de ejemplo
        $stats = [
            'games_played' => $user->games_played ?? 0,
            'games_won' => $user->games_won ?? 0,
            'total_winnings' => $user->total_winnings ?? 0,
            'current_streak' => $user->current_streak ?? 0,
            'best_streak' => $user->best_streak ?? 0,
            'level' => $user->level ?? 1,
            'experience_points' => $user->experience_points ?? 0,
            'achievements_count' => $user->achievements()->count() ?? 0,
            'favorite_room' => $user->favorite_room ?? null,
            'join_date' => $user->created_at->format('Y-m-d'),
            'last_active' => $user->last_active_at?->format('Y-m-d H:i:s') ?? $user->updated_at->format('Y-m-d H:i:s'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
