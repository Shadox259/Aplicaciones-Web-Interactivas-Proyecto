<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['rol', 'q', 'estado']);

        $users = User::query()
            ->withCount('books')
            ->when(filled($filters['rol'] ?? null), fn ($query) => $query->where('role', $filters['rol']))
            ->when(filled($filters['estado'] ?? null), fn ($query) => $query->where('is_active', $filters['estado'] === 'activos'))
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
                $term = '%'.trim((string) $filters['q']).'%';

                $query->where(function ($nested) use ($term) {
                    $nested->whereLike('name', $term)
                        ->orWhereLike('email', $term);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('panel.users', [
            'users' => $users,
            'filters' => $filters,
            'roles' => User::ROLES,
            'currentUser' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'bio' => null,
        ]);
        $user->role = $validated['role'];
        $user->is_active = true;
        $user->save();

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($request->user()->is($user)) {
            return back()->with('error', 'No puedes cambiar tu propio rol ni tu estado desde este panel.');
        }

        if ($this->wouldDisableLastActiveAdmin($user, $validated['role'], (bool) $validated['is_active'])) {
            return back()->with('error', 'No puedes dejar al sistema sin un administrador activo.');
        }

        $user->role = $validated['role'];
        $user->is_active = (bool) $validated['is_active'];
        $user->save();

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta desde este panel.');
        }

        if ($this->wouldDisableLastActiveAdmin($user, $user->role, false)) {
            return back()->with('error', 'No puedes desactivar al último administrador activo.');
        }

        $user->is_active = false;
        $user->save();

        return back()->with('success', 'Usuario eliminado (cuenta desactivada). Sus libros se conservan con su creador.');
    }

    private function wouldDisableLastActiveAdmin(User $user, string $newRole, bool $newActive): bool
    {
        if (! $user->isAdmin() || ! $user->is_active) {
            return false;
        }

        if ($newRole === User::ROLE_ADMIN && $newActive) {
            return false;
        }

        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->count() <= 1;
    }
}
