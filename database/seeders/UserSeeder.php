<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@tokoku.test'],
            [
                'name'     => 'Admin TokoKu',
                'password' => Hash::make('password'),
            ]
        );

        // Pastikan user ini punya role admin (aman dipanggil berkali-kali).
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}