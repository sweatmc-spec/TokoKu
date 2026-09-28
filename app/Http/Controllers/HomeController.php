<?php

namespace App\Http\Controllers;

use App\Models\Module;

class HomeController extends Controller
{
    /**
     * Arahkan user ke halaman pertama yang boleh dia buka.
     *
     * Dicari dari permission aksi "view" milik role-nya, lalu diambil
     * modul pertama (urutan seeder) yang punya route. Jadi role baru buatan
     * admin nggak perlu di-hardcode di mana pun.
     */
    public function __invoke()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $moduleIds = $user->getAllPermissions()
            ->where('action', 'view')
            ->pluck('module_id')
            ->filter()
            ->unique();

        $module = Module::whereIn('id', $moduleIds)
            ->whereNotNull('route')
            ->orderBy('id')
            ->first();

        abort_unless($module, 403, 'Role kamu belum punya akses ke halaman mana pun. Hubungi admin.');

        return redirect()->route($module->route);
    }
}