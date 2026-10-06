<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ['name', 'pcs_per_unit'];

    protected $casts = [
        'pcs_per_unit' => 'integer',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_unit', 'unit_id', 'category_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /** true = isi per unit diisi manual saat input pembelian (mis. Box). */
    public function getIsManualAttribute(): bool
    {
        return $this->pcs_per_unit === null;
    }
}
