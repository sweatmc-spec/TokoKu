<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;


    protected $fillable = [
    'name', 'username', 'email', 'avatar', 'password',
];

public function getAvatarUrlAttribute(): string
{
    if (! $this->avatar) {
        return asset('assets/media/avatars/blank.png');
    }

    return Storage::disk('supabase_avatars')->url($this->avatar);

    /**
     * backup minio untuk test kalau mau
    *$host   = request()->getHost();   // otomatis ambil host yang lagi dipakai browser
    *$bucket = config('filesystems.disks.supabase_avatars.bucket');

    *return "http://{$host}:9000/{$bucket}/{$this->avatar}"; */
}

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
