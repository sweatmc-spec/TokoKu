<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // Barang yang dijual = produk (ATK, Misc) atau varian (Pakaian). Baris varian mengisi KEDUANYA.
            // cascade: produk / varian dihapus -> riwayat mutasinya ikut hilang (tidak ada lagi yang dilaporkan).
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();

            $table->dateTime('tanggal');
            // pembelian | pembelian_batal | penjualan | koreksi_penjualan | penyesuaian
            $table->string('tipe', 30);
            $table->unsignedInteger('qty_masuk')->default(0);
            $table->unsignedInteger('qty_keluar')->default(0);

            // Referensi dokumen asal (kode disalin supaya tetap terbaca walau dokumennya sudah dihapus)
            $table->string('ref_type', 20)->nullable();        // purchase | terjual
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_code', 60)->nullable();

            $table->string('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'product_variant_id', 'tanggal'], 'sm_barang_tanggal');
            $table->index('tanggal');
        });

        $this->isiDariDataLama();
    }

    /**
     * Susun riwayat awal dari data yang sudah ada:
     *   1. setiap barang Cek Paket yang sudah dicentang datang  -> masuk ('pembelian')
     *   2. setiap baris Terjual yang ada sekarang               -> keluar ('penjualan')
     *   3. selisih antara hasil hitungan itu dan stok sebenarnya -> baris 'penyesuaian' (diberi keterangan jelas)
     *
     * Edit / hapus Terjual dan batal centang SEBELUM migration ini tidak punya jejak, jadi tidak muncul sebagai koreksi.
     * Kalau stok sekarang berbeda dengan riwayat, selisihnya tidak disembunyikan: tampil sebagai "Penyesuaian stok".
     * Setelah ini, semua perubahan stok dari Cek Paket dan Terjual tercatat otomatis (StockLedger).
     */
    private function isiDariDataLama(): void
    {
        $now    = now();
        $produk = DB::table('products')->pluck('id')->flip();
        $varian = DB::table('product_variants')->pluck('id')->flip();

        // ---- 1. pembelian (barang dicentang datang)
        DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->whereNotNull('pi.received_at')
            ->whereNotNull('pi.product_id')
            ->select('pi.id as id', 'pi.product_id', 'pi.product_variant_id', 'pi.qty_pcs', 'pi.received_at',
                'p.id as purchase_id', 'p.code as purchase_code')
            ->orderBy('pi.id')
            ->chunkById(500, function ($rows) use ($produk, $varian, $now) {
                $batch = [];
                foreach ($rows as $r) {
                    if (! $produk->has($r->product_id) || (int) $r->qty_pcs <= 0) {
                        continue;
                    }
                    $batch[] = [
                        'product_id'         => $r->product_id,
                        'product_variant_id' => $r->product_variant_id && $varian->has($r->product_variant_id) ? $r->product_variant_id : null,
                        'tanggal'            => $r->received_at,
                        'tipe'               => 'pembelian',
                        'qty_masuk'          => (int) $r->qty_pcs,
                        'qty_keluar'         => 0,
                        'ref_type'           => 'purchase',
                        'ref_id'             => $r->purchase_id,
                        'ref_code'           => $r->purchase_code,
                        'keterangan'         => null,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }
                if ($batch) {
                    DB::table('stock_movements')->insert($batch);
                }
            }, 'pi.id', 'id');

        // ---- 2. penjualan
        DB::table('terjual_items as ti')
            ->join('terjuals as t', 't.id', '=', 'ti.terjual_id')
            ->whereNotNull('ti.product_id')
            ->select('ti.id as id', 'ti.product_id', 'ti.product_variant_id', 'ti.qty', 't.sold_at',
                't.id as terjual_id', 't.code as terjual_code')
            ->orderBy('ti.id')
            ->chunkById(500, function ($rows) use ($produk, $varian, $now) {
                $batch = [];
                foreach ($rows as $r) {
                    if (! $produk->has($r->product_id) || (int) $r->qty <= 0) {
                        continue;
                    }
                    $batch[] = [
                        'product_id'         => $r->product_id,
                        'product_variant_id' => $r->product_variant_id && $varian->has($r->product_variant_id) ? $r->product_variant_id : null,
                        'tanggal'            => $r->sold_at,
                        'tipe'               => 'penjualan',
                        'qty_masuk'          => 0,
                        'qty_keluar'         => (int) $r->qty,
                        'ref_type'           => 'terjual',
                        'ref_id'             => $r->terjual_id,
                        'ref_code'           => $r->terjual_code,
                        'keterangan'         => null,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }
                if ($batch) {
                    DB::table('stock_movements')->insert($batch);
                }
            }, 'ti.id', 'id');

        // ---- 3. selisih terhadap stok sebenarnya -> penyesuaian awal
        // varian dulu (baris varian ikut menambah saldo produknya), baru produk.
        $saldoVarian = DB::table('stock_movements')
            ->whereNotNull('product_variant_id')
            ->selectRaw('product_variant_id AS id, SUM(qty_masuk - qty_keluar) AS saldo')
            ->groupBy('product_variant_id')
            ->pluck('saldo', 'id');

        foreach (DB::table('product_variants')->select('id', 'product_id', 'stock')->orderBy('id')->cursor() as $v) {
            $this->penyesuaian((int) $v->product_id, (int) $v->id, (int) $v->stock - (int) ($saldoVarian[$v->id] ?? 0), $now);
        }

        $saldoProduk = DB::table('stock_movements')
            ->selectRaw('product_id AS id, SUM(qty_masuk - qty_keluar) AS saldo')
            ->groupBy('product_id')
            ->pluck('saldo', 'id');

        foreach (DB::table('products')->select('id', 'stock')->orderBy('id')->cursor() as $p) {
            $this->penyesuaian((int) $p->id, null, (int) $p->stock - (int) ($saldoProduk[$p->id] ?? 0), $now);
        }
    }

    private function penyesuaian(int $productId, ?int $variantId, int $selisih, $now): void
    {
        if ($selisih === 0) {
            return;
        }

        DB::table('stock_movements')->insert([
            'product_id'         => $productId,
            'product_variant_id' => $variantId,
            'tanggal'            => $now,
            'tipe'               => 'penyesuaian',
            'qty_masuk'          => max($selisih, 0),
            'qty_keluar'         => max(-$selisih, 0),
            'keterangan'         => 'Penyesuaian awal: selisih stok saat ini dengan riwayat pembelian dan penjualan yang tercatat',
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};