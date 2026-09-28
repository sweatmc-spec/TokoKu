<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Daftar semua role, dengan jumlah user dan jumlah permission aktif.
     */
    public function index()
    {
        $roles = Role::withCount(['permissions', 'users'])->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Role::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'guard_name'  => 'web',
        ]);

        return redirect()->route('roles.index')->with('success', 'Role baru berhasil ditambahkan.');
    }

    public function edit(Role $role)
    {
        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role->update($validated);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        // Role bawaan sistem gak boleh dihapus, karena logic permission kita
        // bergantung pada role "admin" dan "karyawan" yang selalu ada.
        if (in_array($role->name, ['admin', 'karyawan'])) {
            return back()->with('error', 'Role bawaan sistem tidak bisa dihapus.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Role ini masih dipakai oleh user, tidak bisa dihapus.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }

    /**
     * Halaman checklist permission per modul untuk satu role (tombol "Hak Akses").
     */
    public function permissions(Role $role)
    {
        // Tree modul dengan permission-nya (2 level: modul -> anak).
        $modules = Module::whereNull('parent_id')
            ->with(['permissions', 'children.permissions', 'children.children.permissions'])
            ->orderBy('order')
            ->get();

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.permissions', compact('role', 'modules', 'rolePermissions'));
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $selected = $request->input('permissions', []);
        $role->syncPermissions($selected);

        return redirect()
            ->route('roles.index')
            ->with('success', "Akses untuk role \"{$role->name}\" berhasil diperbarui.");
    }
}