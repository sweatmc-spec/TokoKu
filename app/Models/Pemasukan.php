<?php

namespace App\Models;

use App\Models\Concerns\RingkasanKeuangan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pemasukan extends Model
{
    use RingkasanKeuangan;

    public const SUMBER_PENJUALAN = 'penjualan';
    public const SUMBER_MANUAL    = 'manual';

    protected $table = 'pemasukans';

    protected $fillable = ['tanggal', 'sumber', 'terjual_id', 'keterangan', 'nominal', 'user_id'];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'integer',
    ];

    public function terjual(): BelongsTo
    {
        return $this->belongsTo(Terjual::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Hanya pemasukan manual yang boleh diubah / dihapus dari halaman Pemasukan. */
    public function isManual(): bool
    {
        return $this->sumber === self::SUMBER_MANUAL;
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('tanggal', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('tanggal', '<=', $to));
    }
}
