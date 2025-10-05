<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Get user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['roles', 'country', 'wallets']);

        return response()->json([
            'success' => true,
            'profile' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'avatar' => $user->avatar_url ?? null,
                'phone' => $user->phone ?? null,
                'birth_date' => $user->birth_date ?? null,
                'country_code' => $user->country?->iso_code_2,
                'country_name' => $user->country?->name,
                'role' => $user->roles->first()?->prefix,
                'verified' => !is_null($user->email_verified_at),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'birth_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'profile' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date,
            ],
        ]);
    }

    /**
     * Update user password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente',
        ]);
    }

    /**
     * Upload user avatar
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'], // 2MB max
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::exists($user->avatar)) {
            Storage::delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update([
            'avatar' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar actualizado exitosamente',
            'avatar_url' => Storage::url($path),
        ]);
    }

    /**
     * Delete user avatar
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar && Storage::exists($user->avatar)) {
            Storage::delete($user->avatar);
        }

        $user->update([
            'avatar' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar eliminado exitosamente',
        ]);
    }

    /**
     * Get user preferences
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'preferences' => $user->preferences ?? [
                'theme' => 'light',
                'language' => 'es',
                'notifications' => true,
                'sound_enabled' => true,
                'auto_mark_numbers' => true,
            ],
        ]);
    }

    /**
     * Update user preferences
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['sometimes', 'in:light,dark'],
            'language' => ['sometimes', 'in:es,en,pt'],
            'notifications' => ['sometimes', 'boolean'],
            'sound_enabled' => ['sometimes', 'boolean'],
            'auto_mark_numbers' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        $currentPreferences = $user->preferences ?? [];
        $newPreferences = array_merge($currentPreferences, $validated);

        $user->update([
            'preferences' => $newPreferences,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Preferencias actualizadas exitosamente',
            'preferences' => $newPreferences,
        ]);
    }

    /**
     * Get user stats
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // TODO: Calculate actual stats from games/transactions
        $stats = [
            'total_games' => 0,
            'total_wins' => 0,
            'win_rate' => 0,
            'total_spent' => 0,
            'total_won' => 0,
            'favorite_room' => null,
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
