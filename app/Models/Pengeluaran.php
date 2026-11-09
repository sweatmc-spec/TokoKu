<?php

namespace App\Models;

use App\Models\Concerns\RingkasanKeuangan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengeluaran extends Model
{
    use RingkasanKeuangan;

    public const SUMBER_PEMBELIAN   = 'pembelian';
    public const SUMBER_OPERASIONAL = 'operasional';

    protected $table = 'pengeluarans';

    protected $fillable = [
        'tanggal', 'sumber', 'purchase_id', 'kategori_pengeluaran_id',
        'keterangan', 'nominal', 'bukti', 'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'integer',
    ];

    /** Cek Paket asal (hanya untuk sumber Pembelian). Nama sales lewat purchase->sales. */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'kategori_pengeluaran_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Hanya pengeluaran operasional yang boleh diubah / dihapus dari halaman Pengeluaran. */
    public function isOperasional(): bool
    {
        return $this->sumber === self::SUMBER_OPERASIONAL;
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('tanggal', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('tanggal', '<=', $to));
    }
}
