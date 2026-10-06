<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPengeluaran extends Model
{
    protected $table = 'kategori_pengeluarans';

    protected $fillable = ['nama', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function pengeluarans(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'kategori_pengeluaran_id');
    }

    /** Kategori bawaan untuk pengeluaran otomatis dari Cek Paket. Dibuat kalau (entah kenapa) belum ada. */
    public static function kulakan(): self
    {
        return static::firstOrCreate(['nama' => 'Kulakan'], ['is_system' => true]);
    }

    /** Kategori yang boleh dipilih di form Pengeluaran manual (tanpa Kulakan). */
    public function scopeManual(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }
}
