<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['category_id', 'profit_harga_id', 'name', 'stock', 'last_cost', 'last_received_at'];

    protected $casts = [
        'stock'            => 'integer',
        'last_cost'        => 'decimal:2',
        'last_received_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Harga Jual produk ini (Master Data > Profit Harga), dipilih saat Tambah Produk. */
    public function profitHarga(): BelongsTo
    {
        return $this->belongsTo(ProfitHarga::class, 'profit_harga_id');
    }

    /** Sales yang menjual produk ini. */
    public function sales(): BelongsToMany
    {
        return $this->belongsToMany(Sales::class, 'product_sales', 'product_id', 'sales_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /** Varian (ukuran, warna, ...) - dipakai untuk kategori yang punya varian, mis. Pakaian. */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
