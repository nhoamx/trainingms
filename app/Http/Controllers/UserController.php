<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Mostrar lista de todos los usuarios.
     */
    public function index()
    {
        $users = User::all();
        return inertia('Users/Index', ['users' => $users]);
    }

    /**
     * Mostrar formulario para crear un usuario.
     */
    public function create()
    {
        $roles = Role::all(); // Obtener todos los roles disponibles
        return inertia('Users/Create', ['roles' => $roles]);
    }

    /**
     * Guardar un nuevo usuario con rol.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Crear usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Asignar rol
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Editar un usuario existente.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            'role' => 'required|exists:roles,name',
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

        // Actualizar rol
        $user->syncRoles([$request->role]);

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
