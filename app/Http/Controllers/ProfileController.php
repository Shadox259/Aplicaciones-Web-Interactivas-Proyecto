<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('store.profile', [
            'user' => $user,
            'libraryCount' => $user->library()->count(),
            'publishedCount' => $user->books()->published()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
        ]);

        $user->name = $data['name'];

        if ($user->canPublish()) {
            $user->bio = $data['bio'] ?? null;
        }

        $user->save();

        return back()->with('success', 'Tu perfil se actualizó correctamente.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = $data['password'];
        $user->save();

        return back()->with('success', 'Tu contraseña se actualizó correctamente.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->isAdmin()) {
            return back()->with('error', 'Un administrador no puede desactivar su propia cuenta desde esta pantalla.');
        }

        $user->is_active = false;
        $user->save();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio')->with('success', 'Tu cuenta fue desactivada. Un administrador puede reactivarla si vuelves a necesitar acceso.');
    }
}
