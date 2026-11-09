<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AbsensiPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Boleh melihat absensi SEMUA karyawan. Kalau tabel permissions kamu punya
        // kolom wajib lain (mis. description / group), tambahkan di array kedua ini.
        Permission::firstOrCreate(
            ['name' => 'absensi.read-all', 'guard_name' => 'web'],
        );

        // Boleh melihat semua pengajuan sakit/izin/cuti dan menyetujui/menolaknya.
        Permission::firstOrCreate(
            ['name' => 'absensi.approve', 'guard_name' => 'web'],
        );

        // Sama seperti RoleSeeder: admin selalu memegang semua permission.
        Role::findByName('admin')->syncPermissions(Permission::all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}