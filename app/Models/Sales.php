<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sales extends Model
{
    protected $table = 'sales';

    protected $fillable = ['name', 'phone', 'address'];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_sales', 'sales_id', 'category_id')
            ->withTimestamps();
    }

    /** Produk yang dijual sales ini (Master Data > Produk Sales). */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_sales', 'sales_id', 'product_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'sales_id');
    }
}
