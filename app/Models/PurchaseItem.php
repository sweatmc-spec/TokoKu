<?php

namespace App\Models;

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

        // Harga Jual produk mengikuti Profit Harga yang dipilih saat Tambah Produk
        // (kalau barang ini tidak memilih, Harga Jual yang sudah ada dibiarkan).
        if ($this->profit_harga_id) {
            $product->profit_harga_id = $this->profit_harga_id;
        }
        $product->save();

        // Pakaian: stok juga dicatat per varian
        if ($this->product_variant_id) {
            $variant = ProductVariant::whereKey($this->product_variant_id)->lockForUpdate()->first();

            if ($variant) {
                $variant->stock += $this->qty_pcs;
                $variant->save();
            }
        }

        if ($this->product_id !== $product->id) {
            $this->update(['product_id' => $product->id]);
        }
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
    }
}
