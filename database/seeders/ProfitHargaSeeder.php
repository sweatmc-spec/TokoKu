<?php

namespace Database\Seeders;

use App\Models\ProfitHarga;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfitHargaSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Rentang harga jual yang diisi (tanpa code)
    |--------------------------------------------------------------------------
    | Default: Rp 1.000 sampai Rp 1.000.000, kelipatan Rp 1.000 = 1.000 baris.
    |
    | Jangan mengisi semua angka 1 sampai 1.000.000 (kelipatan 1): itu 1 juta baris,
    | dan semuanya dimuat ke select di form Tambah Produk sehingga halamannya macet.
    | Ubah $kelipatan kalau butuh lebih rapat (mis. 500) atau lebih jarang (mis. 5000).
    */
    protected int $mulai     = 1000;
    protected int $sampai    = 1000000;
    protected int $kelipatan = 1000;

    /**
     * Aman dijalankan berulang: harga tanpa code yang sudah ada tidak dibuat dobel,
     * dan Profit Harga yang kamu tambah sendiri (atau yang punya code) tidak disentuh.
     */
    public function run(): void
    {
        $semua = range($this->mulai, $this->sampai, max(1, $this->kelipatan));

        $sudahAda = ProfitHarga::whereNull('code')
            ->whereBetween('harga', [$this->mulai, $this->sampai])
            ->pluck('harga')
            ->map(fn ($harga) => (int) $harga)
            ->all();

        $baru = array_values(array_diff($semua, $sudahAda));
        $now  = now();

        foreach (array_chunk($baru, 500) as $chunk) {
            DB::table('profit_harga')->insert(array_map(fn ($harga) => [
                'harga'      => $harga,
                'code'       => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        $this->command?->info(
            count($baru) . ' Profit Harga baru ditambahkan (' . (count($semua) - count($baru)) . ' sudah ada).'
        );
    }
}
