<?php

namespace App\Models;

use App\Services\StockLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'category_id', 'product_id', 'product_variant_id', 'profit_harga_id', 'unit_id', 'product_name', 'variant_label',
        'unit_name', 'unit_qty', 'pcs_per_unit', 'qty_pcs',
        'unit_price', 'total_price', 'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    /** Barang yang sudah dicentang datang (= sudah masuk stok). */
    public function scopeReceived(Builder $query): Builder
    {
        return $query->whereNotNull('received_at');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Teks varian: label terbaru kalau varian masih ada, kalau tidak pakai snapshot. */
    public function getVariantTextAttribute(): ?string
    {
        return $this->variant?->label() ?: $this->variant_label;
    }

    public function profitHarga(): BelongsTo
    {
        return $this->belongsTo(ProfitHarga::class, 'profit_harga_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** Nama unit (snapshot saat dibeli), mis. "Lusin". */
    public function getUnitLabelAttribute(): string
    {
        return $this->unit_name ?: '-';
    }

    /**
     * Masukkan barang ini ke stok: stok produk (dan varian) bertambah, Harga Jual mengikuti
     * Profit Harga yang dipilih. Dipanggil SAAT barang dicentang datang, di dalam DB::transaction().
     */
    public function applyToStock(): void
    {
        $product = $this->product_id
            ? Product::whereKey($this->product_id)->lockForUpdate()->first()
            : null;

        // cadangan untuk data lama yang belum punya product_id: cari berdasarkan nama
        $product ??= Product::where('category_id', $this->category_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($this->product_name)])
            ->lockForUpdate()
            ->first();

        if (! $product) {
            $product = Product::create([
                'category_id' => $this->category_id,
                'name'        => $this->product_name,
                'stock'       => 0,
            ]);
        }

        $product->stock += $this->qty_pcs;
        $product->last_cost = round($this->unit_price / $this->pcs_per_unit, 2);
        $product->last_received_at = now();

        // Harga Jual produk = Profit Harga dari paket TERBARU (menurut tanggal pembelian) di antara barang yang
        // sudah dicek datang dan memilih Profit Harga. Hasilnya TIDAK bergantung pada urutan centang.
        // Paket yang tidak memilih Profit Harga tidak mengubah Harga Jual.
        $hargaJual = $this->hargaJualTerbaru($product->id, termasukIni: true);
        if ($hargaJual !== null) {
            $product->profit_harga_id = $hargaJual;
        }
        $product->save();

        // Pakaian: stok juga dicatat per varian
        $variantId = null;

        if ($this->product_variant_id) {
            $variant = ProductVariant::whereKey($this->product_variant_id)->lockForUpdate()->first();

            if ($variant) {
                $variant->stock += $this->qty_pcs;
                $variant->save();
                $variantId = $variant->id;
            }
        }

        // buku mutasi stok (Laporan Stok): barang masuk, di transaction yang sama
        app(StockLedger::class)->pembelian($this, $product->id, $variantId);

        if ($this->product_id !== $product->id) {
            $this->update(['product_id' => $product->id]);
        }
    }

    /**
     * Profit Harga yang berlaku untuk satu produk: milik paket TERBARU di antara barang yang sudah dicek datang
     * dan memilih Profit Harga. "Terbaru" = tanggal pembelian terbesar, lalu id paket, lalu id barang
     * (jadi dua paket pada tanggal yang sama tetap punya pemenang yang pasti).
     *
     * @param  bool  $termasukIni  true  = barang ini dianggap sudah dicek (saat dicentang, karena received_at baru
     *                                      diisi SESUDAH applyToStock)
     *                             false = barang ini dikeluarkan dari perhitungan (saat centang dibatalkan, karena
     *                                      received_at baru dikosongkan SESUDAH revertFromStock)
     */
    protected function hargaJualTerbaru(int $productId, bool $termasukIni): ?int
    {
        $lain = static::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchase_items.product_id', $productId)
            ->where('purchase_items.id', '!=', $this->id)
            ->whereNotNull('purchase_items.received_at')
            ->whereNotNull('purchase_items.profit_harga_id')
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchases.id')
            ->orderByDesc('purchase_items.id')
            ->first([
                'purchase_items.profit_harga_id',
                'purchases.purchase_date',
                'purchases.id as purchase_id',
                'purchase_items.id as item_id',
            ]);

        if (! $termasukIni || ! $this->profit_harga_id) {
            return $lain ? (int) $lain->profit_harga_id : null;
        }

        if (! $lain) {
            return (int) $this->profit_harga_id;
        }

        // bandingkan urutan "terbaru": [tanggal pembelian, id paket, id barang]
        $ini = [$this->purchase->purchase_date->format('Y-m-d'), (int) $this->purchase_id, (int) $this->id];
        $itu = [substr((string) $lain->purchase_date, 0, 10), (int) $lain->purchase_id, (int) $lain->item_id];

        return $ini >= $itu ? (int) $this->profit_harga_id : (int) $lain->profit_harga_id;
    }

    /**
     * Tarik kembali barang ini dari stok (centang dibatalkan). Ditolak kalau stoknya sudah
     * terlanjur berkurang, supaya stok tidak pernah minus. Harga Jual & harga beli terakhir
     * tidak dikembalikan (tidak ada riwayat nilai sebelumnya).
     */
    public function revertFromStock(): void
    {
        $product = $this->product_id
            ? Product::whereKey($this->product_id)->lockForUpdate()->first()
            : null;

        $variant = $this->product_variant_id
            ? ProductVariant::whereKey($this->product_variant_id)->lockForUpdate()->first()
            : null;

        if (($product && $product->stock < $this->qty_pcs) || ($variant && $variant->stock < $this->qty_pcs)) {
            abort(422, 'Centang tidak bisa dibatalkan: stok ' . $this->product_name . ' sudah berkurang sehingga tidak cukup untuk ditarik kembali.');
        }

        if ($product) {
            $product->stock -= $this->qty_pcs;
            $product->save();
        }

        if ($variant) {
            $variant->stock -= $this->qty_pcs;
            $variant->save();
        }

        // Centang dibatalkan: Harga Jual kembali ke paket terbaru yang MASIH tercentang (kalau ada).
        // Kalau tidak ada paket lain yang memilih Profit Harga, Harga Jual dibiarkan (tidak ada riwayat untuk dikembalikan).
        if ($product) {
            $hargaJual = $this->hargaJualTerbaru($product->id, termasukIni: false);

            if ($hargaJual !== null && (int) $product->profit_harga_id !== $hargaJual) {
                $product->profit_harga_id = $hargaJual;
                $product->save();
            }
        }

        // buku mutasi stok (Laporan Stok): centang dibatalkan = barang keluar dari stok
        if ($product) {
            app(StockLedger::class)->pembelianBatal($this, $product->id, $variant?->id);
        }
    }
}