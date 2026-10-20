<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Sales;
use Illuminate\Database\Seeder;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        # ini kamu seeder sendiri, dia tidak memiliki parent
        $data = [
            [
                'name'       => 'Budi Santoso',
                'phone'      => '081234567801',
                'address'    => 'Jl. Merdeka No. 10',
                'categories' => ['ATK'],
            ],
            [
                'name'       => 'Siti Rahmawati',
                'phone'      => '081234567802',
                'address'    => 'Jl. Sudirman No. 25',
                'categories' => ['Miscellaneous'],
            ],
            [
                'name'       => 'Agus Prasetyo',
                'phone'      => '081234567803',
                'address'    => 'Jl. Ahmad Yani No. 7',
                'categories' => ['Pakaian'],
            ],
            [
                'name'       => 'Dewi Lestari',
                'phone'      => '081234567804',
                'address'    => 'Jl. Gajah Mada No. 18',
                'categories' => ['ATK', 'Miscellaneous', 'Pakaian'],
            ],
        ];

        foreach ($data as $row) {
            $sales = Sales::updateOrCreate(
                ['name' => $row['name']],
                [
                    'phone'   => $row['phone'],
                    'address' => $row['address'],
                ]
            );

            $categoryIds = Category::whereIn('name', $row['categories'])->pluck('id');

            if ($categoryIds->count() !== count($row['categories'])) {
                $this->command?->warn(
                    "Sebagian kategori untuk sales '{$sales->name}' tidak ditemukan. "
                    . 'Pastikan CategorySeeder sudah dijalankan.'
                );
            }

            // sync() supaya aman dijalankan berulang kali (tidak duplikat)
            $sales->categories()->sync($categoryIds);
        }
    }
}