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

#[Fillable(['name', 'username', 'email', 'avatar', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * URL foto profil. Foto dibaca lewat route profile.avatar.show
     * (ProfileController@avatar), bukan langsung dari endpoint S3 Supabase.
     */
    public function getAvatarUrlAttribute(): string
    {
        if (! $this->avatar) {
            return asset('assets/media/avatars/blank.png');
        }

        // 'v' supaya browser memuat ulang foto setelah diganti
        return route('profile.avatar.show', [
            'user' => $this->getKey(),
            'v'    => $this->updated_at?->timestamp,
        ]);
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