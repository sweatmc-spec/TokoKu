<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Daftar modul/fitur yang ada di sistem.
     * Untuk menambah modul baru di masa depan, cukup tambahkan
     * baris baru: 'slug' => 'Label Tampilan'.
     */
    protected array $features = [
        'produk'    => 'Produk',
        'stok'      => 'Stok',
        'penjualan' => 'Penjualan',
        'sales'     => 'Sales',
        'laporan'   => 'Laporan Profit',
        'user'      => 'User Management',
        'role'      => 'Role & Permission',
    ];

    /**
     * Aksi CRUD standar yang dibuat untuk setiap modul.
     */
    protected array $actions = ['create', 'read', 'update', 'delete'];

    public function run(): void
    {
        foreach ($this->features as $slug => $label) {
            foreach ($this->actions as $action) {
                Permission::firstOrCreate(
                    [
                        'name'       => "{$slug}.{$action}",
                        'guard_name' => 'web',
                    ],
                    [
                        'feature' => $label,
                    ]
                );
            }
        }
    }
}