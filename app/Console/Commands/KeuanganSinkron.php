<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\Terjual;
use App\Services\KeuanganService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sekali jalan setelah migrate: buatkan Pemasukan untuk transaksi Terjual lama dan
 * Pengeluaran "Kulakan" untuk Cek Paket lama yang sudah selesai.
 * Aman dijalankan berulang: hanya data yang belum punya catatan yang diproses.
 */
class KeuanganSinkron extends Command
{
    protected $signature = 'keuangan:sinkron';

    protected $description = 'Catat Terjual dan Cek Paket lama yang belum punya baris Pemasukan / Pengeluaran';

    public function handle(KeuanganService $keuangan): int
    {
        $penjualan = 0;
        $pembelian = 0;

        Terjual::query()
            ->doesntHave('pemasukan')
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use ($keuangan, &$penjualan) {
                DB::transaction(function () use ($chunk, $keuangan, &$penjualan) {
                    foreach ($chunk as $terjual) {
                        $keuangan->catatPenjualan($terjual);
                        $penjualan++;
                    }
                });
            });

        Purchase::query()
            ->whereNotNull('completed_at')
            ->doesntHave('pengeluaran')
            ->with('sales:id,name')
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use ($keuangan, &$pembelian) {
                DB::transaction(function () use ($chunk, $keuangan, &$pembelian) {
                    foreach ($chunk as $purchase) {
                        // tanggal pengeluaran = kapan paket itu selesai
                        $keuangan->catatPembelian($purchase, $purchase->completed_at);
                        $pembelian++;
                    }
                });
            });

        $this->info("Pemasukan dibuat dari Terjual : {$penjualan}");
        $this->info("Pengeluaran dibuat dari Cek Paket : {$pembelian}");

        return self::SUCCESS;
    }
}
