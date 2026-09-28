<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    protected array $roles = [
        'admin'    => 'Akses penuh ke seluruh sistem.',
        'karyawan' => 'Akses terbatas sesuai hak yang diberikan admin.',
    ];

    public function run(): void
    {
        foreach ($this->roles as $roleName => $description) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Admin otomatis mendapat semua permission yang ada.
        $admin = Role::findByName('admin');
        $admin->syncPermissions(Permission::all());

        // Role "karyawan" sengaja dibiarkan tanpa permission dulu —
        // diatur manual lewat halaman Role Management.
    }
}