<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function sales(): BelongsToMany
    {
        return $this->belongsToMany(Sales::class, 'category_sales', 'category_id', 'sales_id')
            ->withTimestamps();
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'category_unit', 'category_id', 'unit_id');
    }

    /** Kategori ini punya halaman varian (config/product_variants.php)? */
    public function supportsVariants(): bool
    {
        return in_array($this->slug, config('product_variants.categories', []), true);
    }
}
