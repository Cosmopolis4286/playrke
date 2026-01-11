<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Users\Shared\DocumentType;
use App\Models\Users\User;
use App\Traits\Users\UserTrait;
// Models

// Traits

// Otros
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ProfileController extends Controller
{
    use UserTrait;

    public function index()
    {
        $title = 'Mi Perfil';
        $user = User::select('user_type', 'username', 'country')->where('id', Auth::id())->first();
        $document = DocumentType::getDocumentTypeByCountry($user->country);

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
                Image::make($request->file("foto"))
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
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'new_password.different' => 'La nueva contraseña no puede ser la misma que la contraseña actual.',
            'new_password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return redirect()->back()->with('msg-error', 'Contraseña actual incorrecta.');
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return redirect()->back()->with('message', 'Contraseña actualizada correctamente.');
    }
}
