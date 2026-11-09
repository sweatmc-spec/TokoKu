<?php

namespace App\Services;

use App\Models\KategoriPengeluaran;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Purchase;
use App\Models\Terjual;
use Carbon\CarbonInterface;

/**
 * Pencatatan otomatis ke Pemasukan / Pengeluaran.
 *
 * Service ini TIDAK membuka DB::transaction sendiri. Ia dipanggil dari dalam transaction
 * yang sudah ada (TerjualService::persist dan Purchase::refreshCompletion), supaya transaksi,
 * stok, dan catatan uangnya tersimpan bersamaan: gagal satu, gagal semua.
 *
 * Kedua method memakai updateOrCreate pada kolom unique (terjual_id / purchase_id),
 * jadi aman dipanggil berulang dan tidak pernah membuat baris ganda.
 */
class KeuanganService
{
    /**
     * Satu transaksi Terjual = satu Pemasukan. Nominal = TOTAL transaksi (setelah diskon),
     * bukan jumlah bayar. Dipanggil lagi saat transaksi diedit, jadi nominal dan tanggalnya ikut berubah.
     */
    public function catatPenjualan(Terjual $terjual): Pemasukan
    {
        return Pemasukan::updateOrCreate(
            ['terjual_id' => $terjual->id],
            [
                'tanggal'    => $terjual->sold_at->toDateString(),
                'sumber'     => Pemasukan::SUMBER_PENJUALAN,
                'keterangan' => 'Penjualan kepada ' . $terjual->customer_name,
                'nominal'    => (int) $terjual->total,
                'user_id'    => $terjual->user_id,
            ]
        );
    }

    /**
     * Satu Cek Paket yang selesai = satu Pengeluaran kategori "Kulakan".
     * Nominal = jumlah total_price barang yang BENAR-BENAR sudah dicek (received_at terisi).
     * (Penerimaan di Cek Paket per barang, jadi tidak ada qty diterima sebagian.)
     *
     * @param  CarbonInterface|null  $tanggal  default sekarang; command sinkron mengisi tanggal selesai aslinya
     */
    public function catatPembelian(Purchase $purchase, ?CarbonInterface $tanggal = null): Pengeluaran
    {
        $purchase->loadMissing('sales:id,name');

        $diterima = $purchase->items()->whereNotNull('received_at')->get(['id', 'total_price']);

        return Pengeluaran::updateOrCreate(
            ['purchase_id' => $purchase->id],
            [
                'tanggal'                 => ($tanggal ?? now())->toDateString(),
                'sumber'                  => Pengeluaran::SUMBER_PEMBELIAN,
                'kategori_pengeluaran_id' => KategoriPengeluaran::kulakan()->id,
                'keterangan'              => 'Kulakan ' . $purchase->code . ' dari ' . $purchase->sales->name
                                             . ', ' . $diterima->count() . ' macam barang',
                'nominal'                 => (int) $diterima->sum('total_price'),
                'user_id'                 => auth()->id(),
            ]
        );
    }
}
