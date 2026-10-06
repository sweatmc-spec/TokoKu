<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan penting:
        // 1. Module + Permission dibuat bareng (ModuleSeeder)
        // 2. Role dibuat, admin dapat semua permission
        // 3. User (admin & karyawan) di-assign role-nya
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            KaryawanSeeder::class,
            ModuleSeeder::class,
            AbsensiPermissionSeeder::class,
            WorkLocationSeeder::class,
            CategorySeeder::class,
            UnitSeeder::class,
            ProfitHargaSeeder::class,
        ]);
    }
}