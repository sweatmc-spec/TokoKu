<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Samakan Harga Jual (products.profit_harga_id) semua produk dengan aturan:
 * "Profit Harga dari paket TERBARU (tanggal pembelian) yang sudah dicek datang dan memilih Profit Harga".
 *
 * Kode baru di PurchaseItem hanya berlaku untuk centang berikutnya. Produk yang harganya sudah terlanjur
 * salah tidak berubah sendiri; perintah ini yang memperbaikinya.
 *
 * Tanpa opsi --terapkan perintah ini HANYA menampilkan daftar perubahan dan tidak menyimpan apa pun,
 * jadi harga tidak pernah berubah tanpa Anda lihat dulu.
 * Produk yang tidak punya satu pun barang dicek dengan Profit Harga tidak disentuh.
 */
class ProdukSinkronHargaJual extends Command
{
    protected $signature = 'produk:sinkron-harga-jual {--terapkan : Simpan perubahan (tanpa opsi ini hanya menampilkan daftar)}';

    protected $description = 'Samakan Harga Jual produk dengan Profit Harga paket terbaru yang sudah dicek (pratinjau dulu, --terapkan untuk menyimpan)';

    public function handle(): int
    {
        // urutan "terbaru" sama dengan PurchaseItem::hargaJualTerbaru(): tanggal pembelian, id paket, id barang
        $terbaru = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->whereNotNull('pi.received_at')
            ->whereNotNull('pi.profit_harga_id')
            ->whereNotNull('pi.product_id')
            ->orderByDesc('p.purchase_date')
            ->orderByDesc('p.id')
            ->orderByDesc('pi.id')
            ->get(['pi.product_id', 'pi.profit_harga_id', 'p.code as kode_paket'])
            ->unique('product_id')          // baris pertama per produk = yang terbaru
            ->keyBy('product_id');

        $produk = DB::table('products')
            ->whereIn('id', $terbaru->keys())
            ->get(['id', 'name', 'profit_harga_id'])
            ->keyBy('id');

        $harga = DB::table('profit_harga')->pluck('harga', 'id');
        $rupiah = fn ($n) => $n === null ? '(belum ada)' : 'Rp ' . number_format((float) $n, 0, ',', '.');

        $beda = [];

        foreach ($terbaru as $productId => $t) {
            $p = $produk->get($productId);

            if (! $p || (int) $p->profit_harga_id === (int) $t->profit_harga_id) {
                continue;
            }

            $beda[] = [
                'id'    => (int) $productId,
                'nama'  => $p->name,
                'lama'  => $rupiah($harga[$p->profit_harga_id] ?? null),
                'baru'  => $rupiah($harga[$t->profit_harga_id] ?? null),
                'paket' => $t->kode_paket,
                'ph'    => (int) $t->profit_harga_id,
            ];
        }

        if (! $beda) {
            $this->info('Semua Harga Jual produk sudah sesuai aturan. Tidak ada yang perlu diubah.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Produk', 'Harga jual sekarang', 'Harga jual menurut aturan', 'Dari paket'],
            array_map(fn ($b) => [$b['id'], $b['nama'], $b['lama'], $b['baru'], $b['paket']], $beda)
        );

        if (! $this->option('terapkan')) {
            $this->warn(count($beda) . ' produk akan berubah. Belum ada yang disimpan. Jalankan lagi dengan --terapkan untuk menyimpan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($beda) {
            foreach ($beda as $b) {
                Product::query()->whereKey($b['id'])->update(['profit_harga_id' => $b['ph']]);
            }
        });

        $this->info(count($beda) . ' produk diperbarui.');

        return self::SUCCESS;
    }
}