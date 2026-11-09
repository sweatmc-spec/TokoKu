<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsVariantOptions;
use Illuminate\Database\Seeder;

class VariantColorSeeder extends Seeder
{
    use SeedsVariantOptions;

    public function run(): void
    {
        $this->seedOptions('color', [
            ['Hitam',   '#111827'],
            ['Putih',   '#f9fafb'],
            ['Abu-abu', '#6b7280'],
            ['Merah',   '#dc2626'],
            ['Maroon',  '#7f1d1d'],
            ['Orange',  '#f97316'],
            ['Kuning',  '#facc15'],
            ['Hijau',   '#16a34a'],
            ['Army',    '#4b5320'],
            ['Biru',    '#2563eb'],
            ['Navy',    '#1e3a8a'],
            ['Ungu',    '#7c3aed'],
            ['Pink',    '#ec4899'],
            ['Coklat',  '#92400e'],
            ['Krem',    '#f5e6c8'],
        ]);
    }
}
