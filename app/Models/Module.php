<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'title', 'desc',
        'icon', 'route', 'active_pattern', 'order',
    ];

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

    /**
     * Cek apakah user punya minimal satu permission "view" untuk modul ini
     * ATAU salah satu keturunannya (dipakai buat nampilin/nyembunyiin menu).
     */
    public function isVisibleFor($user): bool
    {
        $ownPermission = $this->permissions()->where('action', 'view')->first();

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