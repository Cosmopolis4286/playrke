<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Shared\DocumentType;
use App\Models\User;
use App\Services\ProfileService;
use App\Traits\Users\UserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    use UserTrait;

    public function __construct(
        protected ProfileService $profileService,
    ) {}

    /**
     * Vista del perfil del usuario.
     *
     * @return \Inertia\Response
     */
    public function index(): \Inertia\Response
    {
        $user = Auth::user()->load(['roles', 'wallets']);
        
        // Obtener el primer wallet del usuario si existe
        $wallet = $user->wallets->first();
        
        // Obtener estadísticas del usuario
        $stats = [
            'games_played' => 0,
            'games_won' => 0,
            'total_winnings' => 0,
            'current_streak' => 0,
            'best_streak' => 0,
            'level' => 1,
            'experience_points' => 0,
            'achievements_count' => 0,
            'join_date' => $user->created_at->format('Y-m-d'),
            'last_active' => $user->last_login_at?->format('Y-m-d H:i:s') ?? $user->updated_at->format('Y-m-d H:i:s'),
        ];

        // Obtener preferencias del usuario
        $preferences = [
            'language' => $user->user_preferences['language'] ?? 'es',
            'timezone' => $user->user_preferences['timezone'] ?? 'America/Caracas',
            'notifications' => $user->user_preferences['notifications'] ?? [
                'email' => true,
                'push' => true,
                'sms' => false,
            ],
            'privacy' => $user->user_preferences['privacy'] ?? [
                'profile_visible' => true,
                'show_email' => false,
                'show_phone' => false,
            ],
            'game_settings' => $user->user_preferences['game_settings'] ?? [
                'sound_enabled' => true,
                'music_enabled' => true,
                'auto_dauber' => false,
                'card_animation' => true,
            ],
        ];

        return Inertia::render('Profile/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->profile_photo_url,
                'phone' => $user->phone_number,
                'birth_date' => $user->birth_date,
                'country' => $user->country_id,
                'bio' => $user->bio,
                'is_verified' => !is_null($user->email_verified_at),
                'created_at' => $user->created_at,
                'roles' => $user->roles,
                'wallet' => $wallet ? [
                    'balance' => $wallet->balance,
                    'currency' => $wallet->currency,
                ] : null,
            ],
            'stats' => $stats,
            'preferences' => $preferences,
        ]);
    }

    public function update($request, $id)
    {
        $user = User::findOrFail($id);

        $files = $request->file("foto");
        $nombreimg = date("Y-m-d hms") . '.' . $files->getClientOriginalExtension();

        if (empty($files) == false) {
            if ($user->foto != $nombreimg) {
                $ruta = public_path('imgSystem/imgUsers/' . $nombreimg);
                InterventionImage::make($request->file("foto"))
                    ->resize(100, null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save($ruta);
                $user->foto = $nombreimg;
            }
        }

        $user->update(['name' => $request->name, 'last_name' => $request->last_name]);

        return redirect()->back();
    }

    /**
     * Obtener datos del perfil para API
     */
    public function getProfileData(): JsonResponse
    {
        $user = Auth::user()->load(['roles', 'wallet']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->profile_photo_url,
                'phone' => $user->phone_number,
                'birth_date' => $user->birth_date,
                'country' => $user->country_id,
                'bio' => $user->bio,
                'is_verified' => !is_null($user->email_verified_at),
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
     * Actualizar perfil del usuario
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
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
            'first_name', 'last_name', 'username', 'email', 'phone_number', 'birth_date', 'country_id', 'bio'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Subir avatar del usuario
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
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
            $avatar = $request->file('avatar');

            // Eliminar avatar anterior si existe
            if ($user->profile_photo_url) {
                $oldPath = str_replace('/storage/', '', $user->profile_photo_url);
                Storage::disk('public')->delete($oldPath);
            }

            // Procesar y guardar la imagen
            $filename = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();
            
            // Crear directorio si no existe
            $directory = 'avatars';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Guardar la imagen original
            $path = $avatar->storeAs($directory, $filename, 'public');
            
            // Opcional: Redimensionar la imagen si se solicita
            if ($request->has('resize') && $request->get('resize')) {
                try {
                    // Aquí podrías usar Intervention Image para redimensionar
                    // Por ahora, guardamos la imagen original
                } catch (\Exception $e) {
                    // Si falla el redimensionamiento, continuamos con la original
                }
            }
            
            // Actualizar el perfil del usuario
            $user->update(['profile_photo_url' => '/storage/' . $path]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatar' => '/storage/' . $path,
                    'filename' => $filename
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Avatar upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error uploading avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar avatar del usuario
     */
    public function deleteAvatar(): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->profile_photo_url) {
                $oldPath = str_replace('/storage/', '', $user->profile_photo_url);
                Storage::disk('public')->delete($oldPath);
                $user->update(['profile_photo_url' => null]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Avatar delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener preferencias del usuario
     */
    public function getPreferences(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'language' => $user->user_preferences['language'] ?? 'es',
                'timezone' => $user->user_preferences['timezone'] ?? 'America/Caracas',
                'notifications' => $user->user_preferences['notifications'] ?? [
                    'email' => true,
                    'push' => true,
                    'sms' => false,
                ],
                'privacy' => $user->user_preferences['privacy'] ?? [
                    'profile_visible' => true,
                    'show_email' => false,
                    'show_phone' => false,
                ],
                'game_settings' => $user->user_preferences['game_settings'] ?? [
                    'sound_enabled' => true,
                    'music_enabled' => true,
                    'auto_dauber' => false,
                    'card_animation' => true,
                ],
            ]
        ]);
    }

    /**
     * Actualizar preferencias del usuario
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
        
        $preferences = array_merge($user->user_preferences ?? [], $request->only([
            'language', 'timezone', 'notifications', 'privacy', 'game_settings'
        ]));

        $user->update(['user_preferences' => $preferences]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data' => $preferences
        ]);
    }

    /**
     * Obtener estadísticas del usuario
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
            'join_date' => $user->created_at->format('Y-m-d'),
            'last_active' => $user->last_login_at?->format('Y-m-d H:i:s') ?? $user->updated_at->format('Y-m-d H:i:s'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Cambiar la contraseña del usuario autenticado
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate(
            [
                'current_password' => ['required', 'current_password'],
                'new_password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
            ],
            [
                'current_password.current_password' => 'La contraseña actual es incorrecta.',
                'new_password.different' => 'La nueva contraseña no puede ser la misma que la contraseña actual.',
                'new_password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            ]
        );

        return $this->handleServiceJson(
            function () use ($request) {
                $this->profileService->changePassword(
                    $request->user()->id,
                    $request->new_password
                );

                // $request->session()->regenerate();

                return [
                    'message' => '✅ Contraseña actualizada correctamente',
                ];
            },
            'No se pudo cambiar la contraseña.'
        );
    }
}
