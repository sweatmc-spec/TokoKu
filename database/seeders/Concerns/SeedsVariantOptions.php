<?php

namespace Database\Seeders\Concerns;

use App\Models\VariantOption;

trait SeedsVariantOptions
{
    /**
     * Isi pilihan bawaan. Aman dijalankan berulang:
     *  - nama yang sudah ada TIDAK diubah (perubahan dari halaman Master Data tidak tertimpa),
     *  - kecuali kode warna yang masih kosong, itu dilengkapi.
     *
     * @param  array<int, string|array{0:string,1:?string}>  $items  'Nama' atau ['Nama', '#hex']
     */
    protected function seedOptions(string $type, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            [$name, $hex] = is_array($item) ? [$item[0], $item[1] ?? null] : [$item, null];

            $existing = VariantOption::ofType($type)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if ($existing) {
                if ($hex && ! $existing->hex) {
                    $existing->update(['hex' => $hex]);
                }
                continue;
            }

            VariantOption::create([
                'type'       => $type,
                'name'       => $name,
                'hex'        => $hex,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
