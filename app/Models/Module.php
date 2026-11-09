<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Module extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'title', 'desc',
        'icon', 'route', 'active_pattern', 'order',
    ];

    /** Hasil isVisibleFor() per user, supaya modul yang sama tidak dihitung ulang dalam satu request. */
    protected array $visibleMemo = [];

    public function parent()
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Module::class, 'parent_id')->orderBy('order');
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    /** Permission "view" milik modul ini (modul induk biasanya tidak punya, hasilnya null). */
    public function viewPermission(): HasOne
    {
        return $this->hasOne(Permission::class)->where('action', 'view');
    }

    /**
     * Seluruh pohon menu (semua level) hanya dengan 2 query, berapa pun kedalamannya:
     * 1 untuk semua modul, 1 untuk permission "view" tiap modul. Relasi children diisi
     * manual dari hasil query yang sama, jadi tidak ada lazy load per modul saat sidebar digambar.
     *
     * @return Collection<int, Module> modul level teratas, lengkap dengan children bertingkat
     */
    public static function menuTree(): Collection
    {
        $modules = static::with('viewPermission')->orderBy('order')->get();

        foreach ($modules as $module) {
            $module->setRelation('children', $modules->where('parent_id', $module->id)->values());
        }

        return $modules->whereNull('parent_id')->values();
    }

    /**
     * Cek apakah user punya minimal satu permission "view" untuk modul ini
     * ATAU salah satu keturunannya (dipakai buat nampilin/nyembunyiin menu).
     */
    public function isVisibleFor($user): bool
    {
        $key = $user->getKey();

        if (! array_key_exists($key, $this->visibleMemo)) {
            $this->visibleMemo[$key] = $this->computeVisibleFor($user);
        }

        return $this->visibleMemo[$key];
    }

    private function computeVisibleFor($user): bool
    {
        // Akses sebagai PROPERTI (bukan viewPermission()) supaya memakai data hasil eager load,
        // tidak query baru ke DB untuk tiap modul.
        $ownPermission = $this->viewPermission;

        if ($ownPermission && $user->can($ownPermission->name)) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child->isVisibleFor($user)) {
                return true;
            }
        }

        return false;
    }
}