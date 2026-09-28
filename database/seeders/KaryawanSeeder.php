<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $karyawan = User::firstOrCreate(
            ['email' => 'karyawan@tokoku.test'],
            [
                'name'     => 'Karyawan Percobaan',
                'password' => Hash::make('password'),
            ]
        );

        // Sengaja belum dikasih permission apapun — biar kamu bisa testing
        // alur "admin buka Role Management > centang akses" dari nol.
        if (! $karyawan->hasRole('karyawan')) {
            $karyawan->assignRole('karyawan');
        }
    }
}