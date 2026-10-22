<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $normalizedEmail = Str::lower(trim((string) $request->input('email')));
        $request->merge(['email' => $normalizedEmail]);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $inactiveUser = User::query()
            ->where('email', $normalizedEmail)
            ->where('is_active', false)
            ->first();

        if ($inactiveUser && Hash::check($credentials['password'], $inactiveUser->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.']);
        }

        if (! Auth::attempt([
            'email' => $normalizedEmail,
            'password' => $credentials['password'],
            'is_active' => true,
        ], (bool) ($credentials['remember'] ?? false))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => trans('auth.failed')]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();

        return redirect()->intended(route($user->homeRoute()))
            ->with('success', 'Bienvenido de nuevo, '.$user->name.'.');
    }

    public function showRegister(Request $request): View
    {
        $selectedRole = $request->string('tipo')->lower()->value() === User::ROLE_AUTHOR
            ? User::ROLE_AUTHOR
            : User::ROLE_CUSTOMER;

        return view('auth.register', [
            'selectedRole' => $selectedRole,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $normalizedEmail = Str::lower(trim((string) $request->input('email')));
        $request->merge(['email' => $normalizedEmail]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in([User::ROLE_CUSTOMER, User::ROLE_AUTHOR])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $normalizedEmail,
            'password' => $data['password'],
            'bio' => null,
        ]);
        $user->role = $data['role'];
        $user->is_active = true;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->homeRoute())
            ->with('success', 'Tu cuenta se creó correctamente.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio')->with('success', 'Tu sesión se cerró correctamente.');
    }
}
