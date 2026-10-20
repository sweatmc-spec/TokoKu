<?php

namespace Database\Seeders;

use App\Models\WorkLocation;
use Illuminate\Database\Seeder;

class WorkLocationSeeder extends Seeder
{
    public function run(): void
    {
        WorkLocation::updateOrCreate(
            ['name' => 'Rumah'],
            [
                'latitude' =>  -0.0270833,
                'longitude' =>  111.4562857,
                'radius_meters' => 100, 
                'is_active' => true,
            ]
        );
         WorkLocation::updateOrCreate(
            ['name' => 'Sekolah'],
            [
                'latitude' => -0.033500,
                'longitude' => 111.462700,
                'radius_meters' => 100, 
                'is_active' => true,
            ]
        );
    }
}