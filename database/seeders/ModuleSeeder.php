<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ModuleSeeder extends Seeder
{
    /** Nama role admin yang otomatis dapat SEMUA permission. */
    protected string $adminRole = 'admin';

    /**
     * Role bawaan beserta permission yang SELALU diberikan saat seeding.
     * Dipakai supaya karyawan yang baru login langsung masuk ke halaman
     * absensi tanpa kena 403. Role akan dibuat kalau belum ada.
     *
     * Sifatnya menambah saja (tidak menghapus permission lain yang sudah
     * diatur admin lewat Role Management). Tambah/ubah daftar ini kalau perlu.
     */
    protected array $defaultRolePermissions = [
        'karyawan' => [
            'absensi-masuk.view',
            'absensi-masuk.create',
        ],
    ];

    /**
     * Struktur tree menu toko.
     *
     * Tiap leaf (gak punya 'children') otomatis dibikinkan permission
     * lewat key 'permissions':
     * - list biasa, misal ['view','create'] -> nama permission jadi
     *   "{slug}.view", "{slug}.create", dst.
     * - associative, misal ['view' => 'user.read'] -> dipakai kalau perlu
     *   nama permission spesifik (contoh: modul Management biar cocok
     *   sama middleware & @can() yang udah dipakai di controller/route).
     * - kalau 'permissions' gak diisi, default-nya cuma ['view'].
     *
     * PENTING: nama permission di sini harus sama persis dengan yang
     * dipakai di middleware 'permission:...' pada routes/web.php.
     */
    protected function tree(): array
    {
        return [
            [
                'name' => 'Dashboard', 'slug' => 'dashboard', 'icon' => 'ki-element-11',
                'route' => 'dashboard', 'active_pattern' => 'dashboard*', 'order' => 1,
                'permissions' => ['view'],
            ],
            [
                'name' => 'Absensi', 'slug' => 'absensi', 'icon' => 'ki-calendar-tick', 'order' => 2,
                'children' => [
                    [
                        'name' => 'Absensi Masuk', 'slug' => 'absensi-masuk',
                        'route' => 'absensi.index', 'active_pattern' => 'absensi', 'order' => 1,
                        'permissions' => ['view', 'create'],
                    ],
                    [
                        'name' => 'Total Absensi', 'slug' => 'absensi-total',
                        'route' => 'absensi.laporan', 'active_pattern' => 'absensi/laporan*', 'order' => 2,
                        'permissions' => ['view'],
                    ],
                ],
            ],
            [
                'name' => 'Penjualan', 'slug' => 'penjualan', 'icon' => 'ki-basket-ok', 'order' => 3,
                'children' => [
                    ['name' => 'Terjual', 'slug' => 'penjualan-terjual', 'active_pattern' => 'penjualan/terjual*', 'permissions' => ['view'], 'order' => 1],
                    ['name' => 'Pembayaran', 'slug' => 'penjualan-pembayaran', 'active_pattern' => 'pembayaran*', 'permissions' => ['view'], 'order' => 2],
                ],
            ],
            [
                'name' => 'Produk', 'slug' => 'produk', 'icon' => 'ki-parcel', 'order' => 4,
                'children' => [
                    ['name' => 'Stok Pakaian', 'slug' => 'stok-pakaian', 'active_pattern' => 'stok/pakaian*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 1],
                    ['name' => 'Stok ATK', 'slug' => 'stok-atk', 'active_pattern' => 'stok/atk*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 2],
                    ['name' => 'Stok Miscellaneous', 'slug' => 'stok-miscellaneous', 'active_pattern' => 'stok/miscellaneous*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 3],
                    ['name' => 'Tambah Produk', 'slug' => 'produk-tambah', 'active_pattern' => 'stok/tambah*', 'permissions' => ['view', 'create'], 'order' => 4],
                    ['name' => 'Produk Sales', 'slug' => 'produk-sales', 'active_pattern' => 'sales/produk*', 'permissions' => ['view'], 'order' => 5],
                ],
            ],
            [
                'name' => 'Master Data', 'slug' => 'master-data', 'icon' => 'ki-address-book', 'order' => 5,
                'children' => [
                    ['name' => 'Nama Sales', 'slug' => 'master-nama-sales', 'active_pattern' => 'sales', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 1],
                    // Isi kategori di-hardcode lewat seeder, jadi cukup bisa dilihat saja.
                    ['name' => 'Kategori', 'slug' => 'master-kategori-produk', 'active_pattern' => 'stok/kategori*', 'permissions' => ['view'], 'order' => 2],
                    ['name' => 'Metode Pembayaran', 'slug' => 'master-metode-pembayaran', 'active_pattern' => 'metode-pembayaran*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 3],
                ],
            ],
            [
                'name' => 'Keuangan', 'slug' => 'keuangan', 'icon' => 'ki-dollar', 'order' => 6,
                'children' => [
                    ['name' => 'Pemasukan', 'slug' => 'keuangan-pemasukan', 'active_pattern' => 'keuangan/pemasukan*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 1],
                    ['name' => 'Pengeluaran', 'slug' => 'keuangan-pengeluaran', 'active_pattern' => 'keuangan/pengeluaran*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 2],
                ],
            ],
            [
                'name' => 'Laporan', 'slug' => 'laporan', 'icon' => 'ki-chart-simple', 'order' => 7,
                'children' => [
                    ['name' => 'Laporan Penjualan', 'slug' => 'laporan-penjualan', 'active_pattern' => 'laporan/penjualan*', 'permissions' => ['view'], 'order' => 1],
                    ['name' => 'Laporan Stok', 'slug' => 'laporan-stok', 'active_pattern' => 'laporan/stok*', 'permissions' => ['view'], 'order' => 2],
                    ['name' => 'Laporan Keuangan', 'slug' => 'laporan-keuangan', 'active_pattern' => 'laporan/keuangan*', 'permissions' => ['view'], 'order' => 3],
                ],
            ],
            [
                'name' => 'Management', 'slug' => 'management', 'icon' => 'ki-shield-tick', 'order' => 8,
                'children' => [
                    [
                        'name' => 'Pengguna', 'slug' => 'management-pengguna',
                        'route' => 'users.index', 'active_pattern' => 'users*', 'order' => 1,
                        // pakai nama permission lama biar cocok sama middleware & @can() yang udah ada
                        'permissions' => ['view' => 'user.read', 'create' => 'user.create', 'edit' => 'user.update', 'delete' => 'user.delete'],
                    ],
                    [
                        'name' => 'Role', 'slug' => 'management-role',
                        'route' => 'roles.index', 'active_pattern' => 'roles*', 'order' => 2,
                        'permissions' => ['view' => 'role.read', 'create' => 'role.create', 'edit' => 'role.update', 'delete' => 'role.delete', 'permission-access' => 'role.permissions'],
                    ],
                    [
                        'name' => 'Permission', 'slug' => 'management-permission',
                        'route' => 'permissions.index', 'active_pattern' => 'permissions*', 'order' => 3,
                        'permissions' => ['view' => 'permission.read'],
                    ],
                ],
            ],
        ];
    }

    protected function seedModules(array $modules, ?int $parentId = null): void
    {
        foreach ($modules as $item) {
            $module = Module::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'parent_id'      => $parentId,
                    'name'           => $item['name'],
                    'title'          => $item['title'] ?? $item['name'],
                    'desc'           => $item['desc'] ?? null,
                    'icon'           => $item['icon'] ?? null,
                    'route'          => $item['route'] ?? null,
                    'active_pattern' => $item['active_pattern'] ?? null,
                    'order'          => $item['order'] ?? 0,
                ]
            );

            if (! empty($item['children'])) {
                $this->seedModules($item['children'], $module->id);
            } else {
                $this->seedModulePermissions($item, $module);
            }
        }
    }

    /** Nama permission yang sudah dipakai, buat mendeteksi duplikat antar modul. */
    protected array $seenPermissionNames = [];

    protected function seedModulePermissions(array $item, Module $module): void
    {
        $actions = $item['permissions'] ?? ['view'];

        // Deskripsi bahasa Indonesia per aksi, dipakai di halaman Role Management.
        $labels = [
            'view'              => 'Melihat',
            'create'            => 'Menambah',
            'edit'              => 'Mengubah',
            'delete'            => 'Menghapus',
            'permission-access' => 'Mengatur hak akses',
        ];

        $isAssoc = array_keys($actions) !== range(0, count($actions) - 1);

        foreach ($actions as $key => $value) {
            $action = $isAssoc ? $key : $value;
            $name = $isAssoc ? $value : "{$module->slug}.{$action}";
            $label = $labels[$action] ?? ucfirst($action);

            if (isset($this->seenPermissionNames[$name])) {
                throw new \RuntimeException(
                    "Nama permission '{$name}' dipakai dua kali (modul '{$this->seenPermissionNames[$name]}' dan '{$module->slug}'). Nama permission harus unik."
                );
            }
            $this->seenPermissionNames[$name] = $module->slug;

            Permission::updateOrCreate(
                [
                    'name'       => $name,
                    'guard_name' => 'web',
                ],
                [
                    'module_id' => $module->id,
                    'action'    => $action,
                    'desc'      => "{$label} {$module->name}",
                ]
            );
        }
    }

    /** Modul yang sudah dihapus dari tree (permission-nya ikut dihapus). */
    protected array $obsoleteModules = [
        'akun',                  // profil sekarang terbuka untuk semua user login
        'master-kategori-sales', // Kategori Sales dihapus
    ];

    /** Permission yang dulu ada di modul yang masih dipakai, tapi sekarang dibuang. */
    protected array $obsoletePermissions = [
        // Kategori sekarang view-only
        'master-kategori-produk.create',
        'master-kategori-produk.edit',
        'master-kategori-produk.delete',
    ];

    /**
     * Bersihkan modul/permission lama yang sudah tidak ada di tree(),
     * supaya tidak nyangkut di database lama dan halaman Role Management.
     */
    protected function cleanupObsolete(): void
    {
        Permission::whereIn('name', $this->obsoletePermissions)->delete();

        foreach (Module::whereIn('slug', $this->obsoleteModules)->get() as $module) {
            Permission::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }

    /**
     * Admin selalu memegang SEMUA permission yang ada, termasuk permission
     * baru hasil seeder ini. Role lain (buatan admin lewat Role Management)
     * tidak disentuh sama sekali.
     */
    protected function syncAdminPermissions(): void
    {
        $roleClass = app(PermissionRegistrar::class)->getRoleClass();

        $admin = $roleClass::where('name', $this->adminRole)
            ->where('guard_name', 'web')
            ->first();

        if (! $admin) {
            $this->command?->warn("Role '{$this->adminRole}' belum ada, permission belum di-sync. Jalankan seeder role dulu lalu ulangi ModuleSeeder.");

            return;
        }

        $admin->syncPermissions(Permission::where('guard_name', 'web')->get());
    }

    /**
     * Pastikan role bawaan ada dan punya permission default-nya.
     * Aman dijalankan berulang kali.
     */
    protected function seedDefaultRoles(): void
    {
        $roleClass = app(PermissionRegistrar::class)->getRoleClass();

        foreach ($this->defaultRolePermissions as $roleName => $permissionNames) {
            $role = $roleClass::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            $permissions = Permission::where('guard_name', 'web')
                ->whereIn('name', $permissionNames)
                ->get();

            $role->givePermissionTo($permissions);
        }
    }

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $this->seedModules($this->tree());
        $this->cleanupObsolete();
        $this->syncAdminPermissions();
        $this->seedDefaultRoles();

        $registrar->forgetCachedPermissions();
    }
}