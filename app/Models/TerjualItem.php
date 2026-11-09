<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerjualItem extends Model
{
    protected $fillable = [
        'terjual_id', 'product_id', 'product_variant_id',
        'product_name', 'variant_label',
        'qty', 'unit_price', 'harga_modal', 'total_price',
    ];

    protected $casts = [
        'qty'         => 'integer',
        'unit_price'  => 'integer',
        // modal per pcs (2 desimal, dari products.last_cost); null = tidak diketahui
        'harga_modal' => 'float',
        'total_price' => 'integer',
    ];

    public function terjual(): BelongsTo
    {
        return $this->belongsTo(Terjual::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Kunci unik barang di form kasir: "v:{id varian}" untuk pakaian (per varian),
     * "p:{id produk}" untuk produk tanpa varian.
     */
    public function getItemKeyAttribute(): string
    {
        return $this->product_variant_id
            ? 'v:' . $this->product_variant_id
            : 'p:' . $this->product_id;
    }
}
