<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;
use App\Http\Requests\validationPerfil;
use App\Models\DocumentType;
use App\Traits\UserVal;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use UserVal;

    public function index()
    {
        $title = 'Mi Perfil';
        $user = User::select('user_type', 'username', 'country')->where('id', Auth::id())->first();
        $document = DocumentType::getDocumentTypeByCountry($user->country);
        $wallet = $this->wallet();

        return view('modules.all.profile.index', compact('title', 'user', 'document', 'wallet'));
    }

    public function update(validationPerfil $request, $id)
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
}
