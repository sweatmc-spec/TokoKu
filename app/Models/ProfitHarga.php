<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitHarga extends Model
{
    protected $table = 'profit_harga';

    protected $fillable = ['harga', 'code'];

    protected $casts = [
        'harga' => 'integer',
    ];

    /** Stok (produk) yang memakai Profit Harga ini: satu Profit Harga, banyak stok. */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'profit_harga_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'profit_harga_id');
    }

    /** "Rp 120.000,00" - satu-satunya format Rupiah di PHP (JS di form memakai format yang sama). */
    public function getHargaFormattedAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->harga, 2, ',', '.');
    }
}
