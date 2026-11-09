<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Terjual extends Model
{
    protected $fillable = [
        'code', 'sold_at', 'customer_name', 'user_id',
        'payment_method_id', 'payment_method_name',
        'subtotal', 'discount_type', 'discount_value', 'discount_amount',
        'total', 'paid_amount', 'change_amount', 'note',
    ];

    protected $casts = [
        'sold_at'         => 'datetime',
        'subtotal'        => 'integer',
        'discount_value'  => 'integer',
        'discount_amount' => 'integer',
        'total'           => 'integer',
        'paid_amount'     => 'integer',
        'change_amount'   => 'integer',
    ];

    /** "Rp 120.000,00" - format yang sama dengan ProfitHarga::harga_formatted. */
    public static function rupiah(int|float|null $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 2, ',', '.');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TerjualItem::class);
    }

    /** Kasir yang menginput transaksi. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** Satu transaksi = satu baris Pemasukan (dibuat otomatis oleh KeuanganService). */
    public function pemasukan(): HasOne
    {
        return $this->hasOne(Pemasukan::class);
    }

    /** Nama metode: nama terbaru kalau masih ada di Master Data, kalau tidak pakai snapshot. */
    public function getPaymentMethodTextAttribute(): string
    {
        return $this->paymentMethod?->name ?: ($this->payment_method_name ?: '-');
    }

    /** "10%" atau "Rp 5.000,00"; null kalau tidak ada diskon. */
    public function getDiscountLabelAttribute(): ?string
    {
        if ($this->discount_amount <= 0) {
            return null;
        }

        return $this->discount_type === 'persen'
            ? $this->discount_value . '%'
            : self::rupiah($this->discount_amount);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn ($q) => $q->where(
            fn ($w) => $w->whereLike('code', '%' . $term . '%')
                ->orWhereLike('customer_name', '%' . $term . '%')
        ));
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('sold_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('sold_at', '<=', $to));
    }
}
