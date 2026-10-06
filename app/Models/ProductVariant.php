<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'size', 'color', 'material', 'style', 'stock', 'variant_key',
    ];

    protected $casts = [
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'product_variant_id');
    }

    /** Label singkat, mis. "Hitam / M / Katun / Lengan Pendek" (bagian kosong dilewati). */
    public function label(): string
    {
        return collect([$this->color, $this->size, $this->material, $this->style])->filter()->implode(' / ');
    }

    /** Kunci unik kombinasi varian dalam satu produk. */
    public static function makeKey(string $size, string $color, ?string $material, ?string $style): string
    {
        return mb_strtolower(implode('|', [$size, $color, $material ?? '', $style ?? '']));
    }
}
