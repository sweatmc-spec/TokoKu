<?php

namespace App\Http\Controllers;

use App\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Permission dibuat otomatis lewat ModuleSeeder (per modul x aksi),
     * jadi halaman ini read-only — cuma buat lihat daftar lengkapnya.
     * Untuk mengaktifkan permission ke role tertentu, dilakukan di
     * halaman Role Management > tombol Hak Akses.
     */
    public function index()
    {
        $permissions = Permission::with('module')
            ->get()
            ->groupBy(fn ($permission) => $permission->module?->name ?? 'Lainnya');

        return view('permissions.index', compact('permissions'));
    }
}