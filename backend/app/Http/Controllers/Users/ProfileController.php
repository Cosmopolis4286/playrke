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

class ProfileController extends Controller
{
    use UserTrait;

    public function __construct(
        protected ProfileService $profileService,
    ) {}

    public function index()
    {
        $title = 'Mi Perfil';
        $user = User::select('username', 'country_id', 'first_name', 'last_name', 'email', 'profile_photo_url', 'phone_number')->where('id', Auth::id())->first();
        $document = $user->country_id ? \App\Models\Shared\DocumentType::where('country_id', $user->country_id)->where('status', true)->first() : null;

        return view('modules.all.profile.index', compact('title', 'user', 'document'));
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
