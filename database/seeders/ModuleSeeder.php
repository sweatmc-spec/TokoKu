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
            // karyawan boleh mengajukan sakit / izin / cuti dan memantau statusnya
            'absensi-pengajuan.view',
            'absensi-pengajuan.create',
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
                        // view     -> melihat laporan miliknya sendiri
                        // view-all -> rekap semua karyawan + detail tiap karyawan (dipakai admin)
                        'permissions' => ['view' => 'absensi-total.view', 'view-all' => 'absensi-total.view-all'],
                    ],
                    [
                        'name' => 'Pengajuan', 'slug' => 'absensi-pengajuan',
                        'route' => 'pengajuan.index', 'active_pattern' => 'pengajuan*', 'order' => 3,
                        // view    -> daftar pengajuan miliknya + foto bukti
                        // create  -> kirim sakit / izin / cuti
                        // approve -> lihat semua pengajuan, setujui / tolak izin & cuti
                        'permissions' => ['view', 'create', 'approve'],
                    ],
                ],
            ],
            [
                'name' => 'Penjualan', 'slug' => 'penjualan', 'icon' => 'ki-basket-ok', 'order' => 3,
                'children' => [
                    // view -> daftar + modal Detail, edit -> ubah transaksi, delete -> hapus transaksi (stok dikembalikan)
                    ['name' => 'Terjual', 'slug' => 'penjualan-terjual', 'route' => 'penjualan.terjual.index', 'active_pattern' => 'penjualan/terjual*', 'permissions' => ['view', 'edit', 'delete'], 'order' => 1],
                    // view -> membuka form kasir, create -> menyimpan transaksi baru
                    ['name' => 'Pembayaran', 'slug' => 'penjualan-pembayaran', 'route' => 'penjualan.pembayaran.create', 'active_pattern' => 'penjualan/pembayaran*', 'permissions' => ['view', 'create'], 'order' => 2],
                ],
            ],
            [
                'name' => 'Produk', 'slug' => 'produk', 'icon' => 'ki-parcel', 'order' => 4,
                'children' => [
                    // Stok bertambah otomatis dari pembelian (Produk Sales), jadi stok hanya view.
                    ['name' => 'Stok Pakaian', 'slug' => 'stok-pakaian', 'route' => 'stok.pakaian', 'active_pattern' => 'stok/pakaian*', 'permissions' => ['view'], 'order' => 1],
                    ['name' => 'Stok ATK', 'slug' => 'stok-atk', 'route' => 'stok.atk', 'active_pattern' => 'stok/atk*', 'permissions' => ['view'], 'order' => 2],
                    ['name' => 'Stok Miscellaneous', 'slug' => 'stok-miscellaneous', 'route' => 'stok.miscellaneous', 'active_pattern' => 'stok/miscellaneous*', 'permissions' => ['view'], 'order' => 3],
                    // view -> membuka form, create -> menyimpan pembelian baru
                    ['name' => 'Tambah Produk', 'slug' => 'produk-tambah', 'route' => 'purchases.create', 'active_pattern' => 'stok/tambah*', 'permissions' => ['view', 'create'], 'order' => 4],
                    // Progress paket dari sales.
                    // view -> daftar & detail, edit -> ubah isi pembelian, delete -> hapus seluruh pembelian,
                    // receive -> centang "barang sudah datang"
                    ['name' => 'Cek Paket', 'slug' => 'cek-paket', 'route' => 'purchases.index', 'active_pattern' => 'cek-paket*', 'permissions' => ['view', 'edit', 'delete', 'receive'], 'order' => 5],
                ],
            ],
            [
                'name' => 'Master Data', 'slug' => 'master-data', 'icon' => 'ki-address-book', 'order' => 5,
                'children' => [
                    ['name' => 'Kategori', 'slug' => 'master-kategori-produk', 'route' => 'master-data.categories.index', 'active_pattern' => 'master-data/kategori*', 'permissions' => ['view'], 'order' => 1],
                    ['name' => 'Metode Pembayaran', 'slug' => 'master-metode-pembayaran', 'route' => 'master-data.payment-methods.index', 'active_pattern' => 'master-data/metode-pembayaran*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 2],
                    // Satuan pembelian (Pcs, Lusin, Kodi, Box, ...) + kategori tempat unit itu berlaku.
                    ['name' => 'Unit', 'slug' => 'master-unit', 'route' => 'master-data.units.index', 'active_pattern' => 'master-data/unit*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 3],

                    // Harga jual per pcs. Dipilih di Tambah Produk, tampil sebagai Harga Jual di halaman Stok.
                    ['name' => 'Profit Harga', 'slug' => 'master-profit-harga', 'route' => 'master-data.profit-harga.index', 'active_pattern' => 'master-data/profit-harga*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 4],

                    // Kategori untuk Pengeluaran operasional. "Kulakan" bawaan, tidak bisa diubah / dihapus.
                    ['name' => 'Kategori Pengeluaran', 'slug' => 'master-kategori-pengeluaran', 'route' => 'master-data.expense-categories.index', 'active_pattern' => 'master-data/pengeluaran-kategori*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 7],

                    // ---- Grup: Sales ----
                    [
                        'name' => 'Sales', 'slug' => 'master-sales', 'order' => 5,
                        'children' => [
                            ['name' => 'Nama Sales', 'slug' => 'master-nama-sales', 'route' => 'master-data.sales.index', 'active_pattern' => 'master-data/nama-sales*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 1],
                            // Produk yang dijual tiap sales. Hanya produk di sini yang bisa dipilih di Tambah Produk.
                            // Nama produk berkategori Pakaian membuka halaman varian (edit varian memakai izin 'edit').
                            ['name' => 'Produk Sales', 'slug' => 'master-produk-sales', 'route' => 'master-data.products.index', 'active_pattern' => 'master-data/produk-sales*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 2],
                        ],
                    ],

                    // ---- Grup: Variasi Pakaian (pilihan untuk halaman varian produk) ----
                    [
                        'name' => 'Variasi Pakaian', 'slug' => 'master-variasi-pakaian', 'order' => 6,
                        'children' => [
                            ['name' => 'Warna', 'slug' => 'master-warna', 'route' => 'master-data.color.index', 'active_pattern' => 'master-data/warna*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 1],
                            ['name' => 'Ukuran', 'slug' => 'master-ukuran', 'route' => 'master-data.size.index', 'active_pattern' => 'master-data/ukuran*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 2],
                            ['name' => 'Bahan', 'slug' => 'master-bahan', 'route' => 'master-data.material.index', 'active_pattern' => 'master-data/bahan*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 3],
                            ['name' => 'Model', 'slug' => 'master-model', 'route' => 'master-data.style.index', 'active_pattern' => 'master-data/model*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Keuangan', 'slug' => 'keuangan', 'icon' => 'ki-dollar', 'order' => 6,
                'children' => [
                    ['name' => 'Pemasukan', 'slug' => 'keuangan-pemasukan', 'route' => 'keuangan.pemasukan.index', 'active_pattern' => 'keuangan/pemasukan*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 1],
                    ['name' => 'Pengeluaran', 'slug' => 'keuangan-pengeluaran', 'route' => 'keuangan.pengeluaran.index', 'active_pattern' => 'keuangan/pengeluaran*', 'permissions' => ['view', 'create', 'edit', 'delete'], 'order' => 2],
                ],
            ],
            [
                'name' => 'Laporan', 'slug' => 'laporan', 'icon' => 'ki-chart-simple', 'order' => 7,
                'children' => [
                    // view -> melihat laporan, export -> unduh Excel dan PDF / cetak
                    ['name' => 'Laporan Penjualan', 'slug' => 'laporan-penjualan', 'route' => 'laporan.penjualan.index', 'active_pattern' => 'laporan/penjualan*', 'permissions' => ['view', 'export'], 'order' => 1],
                    ['name' => 'Laporan Stok', 'slug' => 'laporan-stok', 'active_pattern' => 'laporan/stok*', 'permissions' => ['view'], 'order' => 2],
                    // view -> melihat laporan, export -> unduh Excel dan PDF / cetak
                    ['name' => 'Laporan Keuangan', 'slug' => 'laporan-keuangan', 'route' => 'laporan.keuangan.index', 'active_pattern' => 'laporan/keuangan*', 'permissions' => ['view', 'export'], 'order' => 3],
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
            'view-all'          => 'Melihat semua data',
            'approve'           => 'Menyetujui',
            'receive'           => 'Mengonfirmasi barang datang pada',
            'export'            => 'Mengekspor',
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
        'produk-sales',          // di menu Produk berganti nama & slug jadi 'cek-paket'
    ];

    /** Permission yang dulu ada di modul yang masih dipakai, tapi sekarang dibuang. */
    protected array $obsoletePermissions = [
        // Kategori sekarang view-only
        'master-kategori-produk.create',
        'master-kategori-produk.edit',
        'master-kategori-produk.delete',

        // Percobaan awal fitur absensi (sempat dibuat lewat seeder terpisah, tanpa modul).
        // Sekarang diganti absensi-total.view-all dan absensi-pengajuan.approve.
        'absensi.read-all',
        'absensi.approve',

        // Stok sekarang view-only, tambah/edit/hapus lewat pembelian.
        'stok-pakaian.create',
        'stok-pakaian.edit',
        'stok-pakaian.delete',
        'stok-atk.create',
        'stok-atk.edit',
        'stok-atk.delete',
        'stok-miscellaneous.create',
        'stok-miscellaneous.edit',
        'stok-miscellaneous.delete',
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
