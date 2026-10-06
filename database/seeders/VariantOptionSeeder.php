<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Jalankan semua seeder variasi pakaian sekaligus: warna, ukuran, bahan, model. */
class VariantOptionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VariantColorSeeder::class,
            VariantSizeSeeder::class,
            VariantMaterialSeeder::class,
            VariantStyleSeeder::class,
        ]);
    }
}
