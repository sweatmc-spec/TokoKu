<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengajuan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan',
        'foto_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'catatan_admin',
        'alasan_edit',
        'edited_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'reviewed_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Jumlah hari kalender yang dicakup pengajuan (tanggal mulai dan selesai sama-sama dihitung). */
    public function getJumlahHariAttribute(): int
    {
        return (int) $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'cuti' => 'Cuti',
            default => ucfirst($this->type),
        };
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->type) {
            'sakit' => 'badge-light-info',
            'izin' => 'badge-light-primary',
            default => 'badge-light-dark', // cuti
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'menunggu' => 'Menunggu',
            'disetujui' => 'Diterima',
            'ditolak' => 'Ditolak',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'menunggu' => 'badge-light-warning',
            'disetujui' => 'badge-light-success',
            default => 'badge-light-danger', // ditolak
        };
    }
}