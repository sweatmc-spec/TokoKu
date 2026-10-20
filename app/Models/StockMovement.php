<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris buku mutasi stok: stok masuk atau keluar untuk satu barang pada satu waktu.
 * Hanya ditulis lewat App\Services\StockLedger; Laporan Stok hanya membacanya.
 */
class StockMovement extends Model
{
    protected $table = 'stock_movements';

    protected $fillable = [
        'product_id', 'product_variant_id', 'tanggal', 'tipe', 'qty_masuk', 'qty_keluar',
        'ref_type', 'ref_id', 'ref_code', 'keterangan', 'user_id',
    ];

    protected $casts = [
        'tanggal'    => 'datetime',
        'qty_masuk'  => 'integer',
        'qty_keluar' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}