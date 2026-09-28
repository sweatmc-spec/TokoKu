<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Absensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_location_id',
        'type',
        'photo_path',
        'latitude',
        'longitude',
        'distance_meters',
        'is_valid',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'distance_meters' => 'float',
        'is_valid' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    /**
     * URL foto dari disk MinIO (s3). Sesuaikan visibility bucket
     * kalau butuh signed/temporary URL untuk bucket privat.
     */
    
    public function getPhotoUrlAttribute(): string
    {
        return Storage::disk('s3_absensi')->url($this->photo_path);
    }
}