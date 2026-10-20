<?php

namespace App\Services;

use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Terjual;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Buku mutasi stok. Satu-satunya yang menulis ke stock_movements.
 *
 * Seperti KeuanganService, class ini TIDAK membuka DB::transaction sendiri: ia dipanggil dari dalam
 * transaction yang sudah ada (centang Cek Paket, TerjualService), jadi stok dan catatan mutasinya
 * tersimpan bersamaan, gagal satu gagal semua.
 *
 * Yang dicatat:
 *   - Cek Paket: barang dicentang datang (masuk), centang dibatalkan (keluar)
 *   - Terjual  : transaksi baru (keluar); edit transaksi (selisih per barang); hapus transaksi (masuk)
 *
 * Perubahan stok di luar dua alur itu TIDAK tercatat di sini. Laporan Stok mendeteksinya sebagai selisih
 * antara saldo mutasi dan stok aktual, lalu menandai barangnya dengan peringatan.
 */
class StockLedger
{
    public const TIPE_LABEL = [
        'pembelian'         => 'Barang datang (Cek Paket)',
        'pembelian_batal'   => 'Batal centang barang datang',
        'penjualan'         => 'Penjualan',
        'koreksi_penjualan' => 'Koreksi/Pembatalan penjualan',
        'penyesuaian'       => 'Penyesuaian stok',
    ];

    /**
     * @param  array{tanggal?:CarbonInterface|string|null, ref_type?:?string, ref_id?:?int, ref_code?:?string, keterangan?:?string}  $opsi
     */
    public function catat(int $productId, ?int $variantId, string $tipe, int $masuk, int $keluar, array $opsi = []): ?StockMovement
    {
        if ($masuk <= 0 && $keluar <= 0) {
            return null;
        }

        return StockMovement::create([
            'product_id'         => $productId,
            'product_variant_id' => $variantId,
            'tanggal'            => $opsi['tanggal'] ?? now(),
            'tipe'               => $tipe,
            'qty_masuk'          => max($masuk, 0),
            'qty_keluar'         => max($keluar, 0),
            'ref_type'           => $opsi['ref_type'] ?? null,
            'ref_id'             => $opsi['ref_id'] ?? null,
            'ref_code'           => $opsi['ref_code'] ?? null,
            'keterangan'         => $opsi['keterangan'] ?? null,
            'user_id'            => auth()->id(),
        ]);
    }

    // ------------------------------------------------------------------ Cek Paket

    /** Dipanggil dari PurchaseItem::applyToStock() (barang dicentang datang). */
    public function pembelian(PurchaseItem $item, int $productId, ?int $variantId): void
    {
        $this->catat($productId, $variantId, 'pembelian', (int) $item->qty_pcs, 0, [
            'ref_type' => 'purchase',
            'ref_id'   => $item->purchase_id,
            'ref_code' => $item->purchase?->code,
        ]);
    }

    /** Dipanggil dari PurchaseItem::revertFromStock() (centang dibatalkan). */
    public function pembelianBatal(PurchaseItem $item, int $productId, ?int $variantId): void
    {
        $this->catat($productId, $variantId, 'pembelian_batal', 0, (int) $item->qty_pcs, [
            'ref_type'   => 'purchase',
            'ref_id'     => $item->purchase_id,
            'ref_code'   => $item->purchase?->code,
            'keterangan' => 'Centang barang datang dibatalkan',
        ]);
    }

    // ------------------------------------------------------------------ Terjual

    /**
     * Setelah transaksi tersimpan (baru atau diedit): catat SELISIH per barang antara isi lama dan isi baru,
     * bukan seluruh isi lagi. Contoh: qty 5 diedit jadi 3 -> satu baris "koreksi" masuk 2.
     *
     * @param  Collection<int, \App\Models\TerjualItem>  $lama   isi transaksi sebelum diedit (kosong kalau transaksi baru)
     * @param  array<int, array<string, mixed>>           $baris  isi baru (kunci: product_id, product_variant_id, qty)
     */
    public function sinkronTerjual(Terjual $terjual, Collection $lama, array $baris): void
    {
        $baru = $terjual->wasRecentlyCreated;

        $sebelum = $lama->filter(fn ($i) => $i->product_id)->mapWithKeys(fn ($i) => [
            $i->item_key => ['p' => (int) $i->product_id, 'v' => $i->product_variant_id ? (int) $i->product_variant_id : null, 'qty' => (int) $i->qty],
        ]);

        $sesudah = collect($baris)->mapWithKeys(fn ($b) => [
            ($b['product_variant_id'] ? 'v:' . $b['product_variant_id'] : 'p:' . $b['product_id']) => [
                'p' => (int) $b['product_id'], 'v' => $b['product_variant_id'] ? (int) $b['product_variant_id'] : null, 'qty' => (int) $b['qty'],
            ],
        ]);

        $ref = ['ref_type' => 'terjual', 'ref_id' => $terjual->id, 'ref_code' => $terjual->code];

        foreach ($sesudah as $key => $b) {
            $delta = $b['qty'] - ($sebelum[$key]['qty'] ?? 0);

            if ($delta > 0) {
                $this->catat($b['p'], $b['v'], 'penjualan', 0, $delta, $ref + [
                    'tanggal'    => $baru ? $terjual->sold_at : now(),
                    'keterangan' => $baru ? null : (isset($sebelum[$key]) ? 'Edit transaksi: jumlah ditambah' : 'Edit transaksi: barang ditambahkan'),
                ]);
            } elseif ($delta < 0) {
                $this->catat($b['p'], $b['v'], 'koreksi_penjualan', -$delta, 0, $ref + [
                    'keterangan' => 'Edit transaksi: jumlah dikurangi',
                ]);
            }
        }

        // barang yang tadinya ada di transaksi tetapi sekarang dibuang
        foreach ($sebelum as $key => $b) {
            if (! $sesudah->has($key)) {
                $this->catat($b['p'], $b['v'], 'koreksi_penjualan', $b['qty'], 0, $ref + [
                    'keterangan' => 'Edit transaksi: barang dihapus dari transaksi',
                ]);
            }
        }
    }

    /**
     * Transaksi dihapus: seluruh isinya dikembalikan ke stok. Dipanggil SEBELUM transaksinya dihapus.
     * ref_id dikosongkan (dokumennya akan hilang), kodenya tetap tersimpan sebagai teks.
     *
     * @param  Collection<int, \App\Models\TerjualItem>  $lama
     */
    public function batalTerjual(Terjual $terjual, Collection $lama): void
    {
        foreach ($lama as $item) {
            if (! $item->product_id) {
                continue;
            }

            $this->catat((int) $item->product_id, $item->product_variant_id ? (int) $item->product_variant_id : null,
                'koreksi_penjualan', (int) $item->qty, 0, [
                    'ref_type'   => 'terjual',
                    'ref_id'     => null,
                    'ref_code'   => $terjual->code,
                    'keterangan' => 'Pembatalan penjualan: transaksi dihapus',
                ]);
        }
    }
}