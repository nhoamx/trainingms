<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Mostrar lista de todos los usuarios.
     */
    public function index()
    {
        $users = User::with(['organization', 'roles'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role_name, // Using the accessor we already have
                    'organization' => [
                        'name' => $user->organization?->name ?? 'Sin organización',
                        'logo' => $user->organization?->logo ?? null,
                    ],
                    'temporal_password' => $user->temporal_password,
                ];
            });

        return Inertia::render('Users/Index', [
            'title' => 'Usuarios',
            'action' => [
                'title' => 'Crear usuario',
                'route' => route('users.create'),
            ],
            'users' => $users
        ]);
    }

    /**
     * Mostrar formulario para crear un usuario.
     */
    public function create()
    {
        $roles = Role::all()->map(fn($role) => [
                'value' => $role->id,
                'label' => $role->name,
            ]);

        $organizations = Organization::all()->map(fn($organization) => [
                'value' => $organization->id,
                'label' => $organization->name,
            ]);
        return Inertia::render('Users/Create', [
            'roles' => $roles,
            'organizations' => $organizations,
            'title' => 'Crear usuario'
        ]);
    }

    /**
     * Guardar un nuevo usuario con rol.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        //Create the temporary password
        $password = substr(md5(microtime()), 0, 8);

        // Crear usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'temporal_password' => $password,
            'password' => Hash::make($password), // Ahora usamos la misma contraseña temporal
        ]);

        // Asignar rol
        $user->assignRole($request->role);

        if($request->organization){
            $user->organization()->associate($request->organization);
            $user->save();
        }

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'user' => $user->load(['roles', 'organization']),
            'roles' => Role::all()->map(fn($role) => [
                'value' => $role->id,
                'label' => $role->name,
            ]),
            'organizations' => Organization::all()->map(fn($organization) => [
                'value' => $organization->id,
                'label' => $organization->name,
            ]),
            'title' => 'Editar usuario'
        ]);
    }

    /**
     * Editar un usuario existente.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user->name = $request->name;
        $user->email = $request->email;

        // Actualizar rol
        $user->syncRoles([$request->role]);

        if($request->organization){
            $user->organization()->dissociate();
            $user->organization()->associate($request->organization);
        }

        if ($request->reset_password) {
            $password = substr(md5(microtime()), 0, 8);
            $user->temporary_password = $password;
            $user->password = Hash::make($password);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Deshabilitar un usuario.
     */
    public function disable(User $user)
    {
        $user->update(['is_disabled' => true]);

        return back()->with('success', 'Usuario deshabilitado exitosamente.');
    }

    /**
     * Eliminar un usuario solo si eres superadmin.
     */
    public function destroy(User $user)
    {

        if (auth()->user()->hasRole('admin') && $user->hasRole('super-admin')) {
            abort(403, 'No puedes eliminar a un usuario con rol Super Admin.');
        }

        if (auth()->user()->id === $user->id) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado exitosamente.');
    }

    /**
     * Mostrar perfil del usuario autenticado.
     */
    public function showProfile()
    {
        return inertia('Profile/Show', ['user' => auth()->user()]);
    }

    /**
     * Actualizar perfil del usuario autenticado.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Actualizar usuario
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
        ]);

        return back()->with('success', 'Perfil actualizado exitosamente.');
    }
}
