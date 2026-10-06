<?php

namespace Database\Seeders;

use App\Models\WorkLocation;
use Illuminate\Database\Seeder;

class WorkLocationSeeder extends Seeder
{
    public function run(): void
    {
        WorkLocation::updateOrCreate(
            ['name' => 'Toko Pusat'],
            [
                'latitude' => -0.039338,
                'longitude' => 109.306520,
                'radius_meters' => 100, // sesuaikan lagi kalau perlu, ini cuma nilai testing
                'is_active' => true,
            ]
        );
        WorkLocation::updateOrCreate(
            ['name' => 'Rumah'],
            [
                'latitude' => -0.033216,
                'longitude' => 109.327482,
                'radius_meters' => 100, // sesuaikan lagi kalau perlu, ini cuma nilai testing
                'is_active' => true,
            ]
        );
    }
}