<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pakaian',       'description' => 'Produk pakaian dan fashion.'],
            ['name' => 'ATK',           'description' => 'Alat tulis kantor.'],
            ['name' => 'Miscellaneous', 'description' => 'Produk lain-lain di luar kategori utama.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                $category + ['slug' => Str::slug($category['name'])]
            );
        }
    }
}
