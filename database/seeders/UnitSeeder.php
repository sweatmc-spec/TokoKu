<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Unit bawaan (sama seperti config lama). Jalankan SETELAH CategorySeeder.
     *
     * Aman dijalankan berulang: unit yang sudah ada tidak diubah, dan pengaturan
     * kategori hanya diisi untuk unit yang BARU dibuat, jadi perubahan yang kamu
     * lakukan di Master Data > Unit tidak tertimpa.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Pcs',   'pcs_per_unit' => 1,    'categories' => ['pakaian', 'atk', 'miscellaneous']],
            ['name' => 'Lusin', 'pcs_per_unit' => 12,   'categories' => ['pakaian', 'atk']],
            ['name' => 'Kodi',  'pcs_per_unit' => 20,   'categories' => ['pakaian']],
            ['name' => 'Box',   'pcs_per_unit' => null, 'categories' => ['atk', 'miscellaneous']], // isi manual
        ];

        foreach ($units as $row) {
            $unit = Unit::firstOrCreate(
                ['name' => $row['name']],
                ['pcs_per_unit' => $row['pcs_per_unit']]
            );

            if ($unit->wasRecentlyCreated) {
                $unit->categories()->sync(
                    Category::whereIn('slug', $row['categories'])->pluck('id')
                );
            }
        }

        // Hubungkan item pembelian lama (dibuat sebelum ada tabel units) berdasarkan nama unit
        foreach (Unit::all() as $unit) {
            DB::table('purchase_items')
                ->whereNull('unit_id')
                ->whereRaw('LOWER(unit_name) = ?', [mb_strtolower($unit->name)])
                ->update(['unit_id' => $unit->id]);
        }
    }
}
