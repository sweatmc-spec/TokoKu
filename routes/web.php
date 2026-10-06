<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterData\CategoryController;
use App\Http\Controllers\MasterData\PaymentMethodController;
use App\Http\Controllers\MasterData\SalesController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\MasterData\UnitController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\TerjualController;
use App\Http\Controllers\MasterData\ProductController;
use App\Http\Controllers\MasterData\ProductVariantController;
use App\Http\Controllers\MasterData\VariantOptionController;
use App\Http\Controllers\MasterData\ProfitHargaController;
use App\Http\Controllers\MasterData\KategoriPengeluaranController;
use App\Http\Controllers\PemasukanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\Laporan\LaporanKeuanganController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman utama
|--------------------------------------------------------------------------
| Belum login -> /login. Sudah login -> halaman pertama yang boleh
| diakses oleh role-nya (dicek dinamis dari permission, bukan hardcode).
*/
Route::get('/', HomeController::class)->name('home');

/*
|--------------------------------------------------------------------------
| Route untuk tamu (belum login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Route untuk user yang sudah login
|--------------------------------------------------------------------------
| Semua halaman dijaga permission, BUKAN nama role. Nama permission harus
| sama persis dengan yang dibuat ModuleSeeder ("{slug}.{aksi}" atau nama
| khusus untuk modul Management). Role baru yang dibuat admin di halaman
| Role Management otomatis bisa dipakai tanpa ubah kode di sini.
*/
Route::middleware('auth')->group(function () {

    // Logout (semua yang sudah login, tanpa permission)
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Dashboard  -> modul "dashboard"
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard.view');

    // Akun saya (profil sendiri) -> terbuka untuk semua user yang sudah login,
    // tanpa permission dan tanpa menu di sidebar.
    Route::controller(ProfileController::class)
        ->prefix('account')
        ->name('profile.')
        ->group(function () {
            Route::get('/overview', 'edit')->name('edit');
            Route::post('/overview', 'update')->name('update');
            Route::delete('/avatar', 'destroyAvatar')->name('avatar.destroy');
            Route::get('/avatar/{user}', 'avatar')->name('avatar.show');
        });

    // Absensi  -> modul "absensi-masuk" dan "absensi-total"
    Route::controller(AbsensiController::class)
        ->prefix('absensi')
        ->name('absensi.')
        ->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:absensi-masuk.view');
            Route::post('/', 'store')->name('store')->middleware('permission:absensi-masuk.create');
            Route::get('/riwayat', 'riwayat')->name('riwayat')->middleware('permission:absensi-masuk.view');
            Route::get('/laporan', 'laporan')->name('laporan')->middleware('permission:absensi-total.view'); // "Total Absensi"
        });
    // Pengajuan Sakit / Izin / Cuti  -> modul "absensi-pengajuan"
    // Diajukan dari mana saja (tanpa cek lokasi). Karyawan hanya melihat pengajuannya
    // sendiri; yang punya .approve/.edit/.delete melihat semuanya (dicek di PengajuanController).
    Route::controller(PengajuanController::class)
        ->prefix('pengajuan')
        ->name('pengajuan.')
        ->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:absensi-pengajuan.view');
            Route::post('/', 'store')->name('store')->middleware('permission:absensi-pengajuan.create');
            Route::get('/{pengajuan}/foto', 'foto')->name('foto')->middleware('permission:absensi-pengajuan.view');
            Route::put('/{pengajuan}/review', 'review')->name('review')->middleware('permission:absensi-pengajuan.approve');
            Route::put('/{pengajuan}', 'update')->name('update')->middleware('permission:absensi-pengajuan.edit');
            Route::delete('/{pengajuan}', 'destroy')->name('destroy')->middleware('permission:absensi-pengajuan.delete');
        });

        /*
        |----------------------------------------------------------------------
        | Keuangan -> modul "keuangan-pemasukan" dan "keuangan-pengeluaran"
        |----------------------------------------------------------------------
        | Baris Penjualan / Pembelian dibuat otomatis, jadi create/edit/delete hanya
        | berlaku untuk baris Manual / Operasional (dicek lagi di controller).
        */
        Route::prefix('keuangan')
            ->name('keuangan.')
            ->group(function () {

                Route::controller(PemasukanController::class)
                    ->prefix('pemasukan')
                    ->name('pemasukan.')
                    ->group(function () {
                        Route::get('/', 'index')->name('index')->middleware('permission:keuangan-pemasukan.view');
                        Route::post('/', 'store')->name('store')->middleware('permission:keuangan-pemasukan.create');
                        Route::put('/{pemasukan}', 'update')->name('update')->middleware('permission:keuangan-pemasukan.edit');
                        Route::delete('/{pemasukan}', 'destroy')->name('destroy')->middleware('permission:keuangan-pemasukan.delete');
                    });

                Route::controller(PengeluaranController::class)
                    ->prefix('pengeluaran')
                    ->name('pengeluaran.')
                    ->group(function () {
                        Route::get('/', 'index')->name('index')->middleware('permission:keuangan-pengeluaran.view');
                        Route::get('/{pengeluaran}/bukti', 'bukti')->name('bukti')->middleware('permission:keuangan-pengeluaran.view');
                        Route::post('/', 'store')->name('store')->middleware('permission:keuangan-pengeluaran.create');
                        Route::put('/{pengeluaran}', 'update')->name('update')->middleware('permission:keuangan-pengeluaran.edit');
                        Route::delete('/{pengeluaran}', 'destroy')->name('destroy')->middleware('permission:keuangan-pengeluaran.delete');
                    });
            });
   /*
    |----------------------------------------------------------------------
    | Master Data -> modul "master-nama-sales", "master-kategori-produk", "master-metode-pembayaran", "master-unit", "master-produk-sales"
    |----------------------------------------------------------------------
    | Create / edit / hapus dilakukan lewat modal (AJAX), jadi tidak ada
    | route create/edit. Kategori hanya view (data dari CategorySeeder).
    */
    Route::prefix('master-data')
        ->name('master-data.')
        ->group(function () {
 
            // Nama Sales  -> modul "master-nama-sales"
            Route::controller(SalesController::class)
                ->prefix('nama-sales')
                ->name('sales.')
                ->group(function () {
                    Route::get('/', 'index')->name('index')->middleware('permission:master-nama-sales.view');
                    Route::post('/', 'store')->name('store')->middleware('permission:master-nama-sales.create');
                    Route::put('/{sale}', 'update')->name('update')->middleware('permission:master-nama-sales.edit');
                    Route::delete('/{sale}', 'destroy')->name('destroy')->middleware('permission:master-nama-sales.delete');
                });
 
            // Kategori (view only)  -> modul "master-kategori-produk"
            Route::get('/kategori', [CategoryController::class, 'index'])
                ->name('categories.index')
                ->middleware('permission:master-kategori-produk.view');
 
            // Metode Pembayaran  -> modul "master-metode-pembayaran"
            Route::controller(PaymentMethodController::class)
                ->prefix('metode-pembayaran')
                ->name('payment-methods.')
                ->group(function () {
                    Route::get('/', 'index')->name('index')->middleware('permission:master-metode-pembayaran.view');
                    Route::post('/', 'store')->name('store')->middleware('permission:master-metode-pembayaran.create');
                    Route::put('/{payment_method}', 'update')->name('update')->middleware('permission:master-metode-pembayaran.edit');
                    Route::delete('/{payment_method}', 'destroy')->name('destroy')->middleware('permission:master-metode-pembayaran.delete');
                });

            // Kategori Pengeluaran  -> modul "master-kategori-pengeluaran"
            // URL sengaja "pengeluaran-kategori", BUKAN "kategori-pengeluaran": active_pattern menu Kategori
            // ('master-data/kategori*') akan ikut menyala kalau URL-nya diawali "kategori".
            Route::controller(KategoriPengeluaranController::class)
                ->prefix('pengeluaran-kategori')
                ->name('expense-categories.')
                ->group(function () {
                    Route::get('/', 'index')->name('index')->middleware('permission:master-kategori-pengeluaran.view');
                    Route::post('/', 'store')->name('store')->middleware('permission:master-kategori-pengeluaran.create');
                    Route::put('/{kategori_pengeluaran}', 'update')->name('update')->middleware('permission:master-kategori-pengeluaran.edit');
                    Route::delete('/{kategori_pengeluaran}', 'destroy')->name('destroy')->middleware('permission:master-kategori-pengeluaran.delete');
                });
 
            // Unit / satuan pembelian  -> modul "master-unit"
            Route::controller(UnitController::class)
                ->prefix('unit')
                ->name('units.')
                ->group(function () {
                    Route::get('/', 'index')->name('index')->middleware('permission:master-unit.view');
                    Route::post('/', 'store')->name('store')->middleware('permission:master-unit.create');
                    Route::put('/{unit}', 'update')->name('update')->middleware('permission:master-unit.edit');
                    Route::delete('/{unit}', 'destroy')->name('destroy')->middleware('permission:master-unit.delete');
                });
                //profit harga
               Route::controller(ProfitHargaController::class)
                ->prefix('profit-harga')
                ->name('profit-harga.')
                ->group(function () {
                    Route::get('/', 'index')->name('index')->middleware('permission:master-profit-harga.view');
                    Route::post('/', 'store')->name('store')->middleware('permission:master-profit-harga.create');
                    Route::put('/{profit_harga}', 'update')->name('update')->middleware('permission:master-profit-harga.edit');
                    Route::delete('/{profit_harga}', 'destroy')->name('destroy')->middleware('permission:master-profit-harga.delete');
                });
 
            // Produk Sales (produk yang dijual tiap sales)  -> modul "master-produk-sales"
            Route::controller(ProductController::class)
                ->prefix('produk-sales')
                ->name('products.')
                ->group(function () {
                    Route::get('/', 'index')->name('index')->middleware('permission:master-produk-sales.view');
                    Route::post('/', 'store')->name('store')->middleware('permission:master-produk-sales.create');
                    Route::put('/{product}', 'update')->name('update')->middleware('permission:master-produk-sales.edit');
                    Route::delete('/{product}', 'destroy')->name('destroy')->middleware('permission:master-produk-sales.delete');
                });
 
            // Halaman varian produk (ukuran, warna, dll) - hanya untuk kategori yang punya varian, mis. Pakaian
            Route::controller(ProductVariantController::class)
                ->prefix('produk-sales')
                ->name('products.')
                ->group(function () {
                    Route::get('/{product}', 'show')->name('show')->middleware('permission:master-produk-sales.view');
                    Route::put('/{product}/variants', 'sync')->name('variants.sync')->middleware('permission:master-produk-sales.edit');
                });
 
            // Variasi Pakaian: Warna, Ukuran, Bahan, Model
            // Satu controller untuk keempatnya; jenisnya dibawa lewat default 'type'.
            foreach ([
                'color'    => ['warna',  'master-warna'],
                'size'     => ['ukuran', 'master-ukuran'],
                'material' => ['bahan',  'master-bahan'],
                'style'    => ['model',  'master-model'],
            ] as $type => [$uri, $slug]) {
                Route::controller(VariantOptionController::class)
                    ->prefix($uri)
                    ->name($type . '.')
                    ->group(function () use ($type, $slug) {
                        Route::get('/', 'index')->name('index')->defaults('type', $type)->middleware("permission:{$slug}.view");
                        Route::post('/', 'store')->name('store')->defaults('type', $type)->middleware("permission:{$slug}.create");
                        Route::put('/{option}', 'update')->name('update')->defaults('type', $type)->middleware("permission:{$slug}.edit");
                        Route::delete('/{option}', 'destroy')->name('destroy')->defaults('type', $type)->middleware("permission:{$slug}.delete");
                    });
            }
        });
 
    /*
    |----------------------------------------------------------------------
    | Produk -> modul "stok-*", "produk-tambah", "cek-paket"
    |----------------------------------------------------------------------
    | Stok bertambah otomatis dari pembelian (Produk Sales), jadi halaman
    | stok hanya view. Barang dicentang datang di halaman detail; kalau
    | semua sudah datang, pembelian selesai dan stok ditambah.
    */
 
    // Stok Pakaian / ATK / Miscellaneous (satu controller, kategori lewat slug)
   Route::prefix('stok')
       ->name('stok.')
       ->group(function () {
           Route::get('/pakaian', [StockController::class, 'index'])->defaults('categorySlug', 'pakaian')
               ->name('pakaian')->middleware('permission:stok-pakaian.view');
           Route::get('/atk', [StockController::class, 'index'])->defaults('categorySlug', 'atk')
               ->name('atk')->middleware('permission:stok-atk.view');
           Route::get('/miscellaneous', [StockController::class, 'index'])->defaults('categorySlug', 'miscellaneous')
               ->name('miscellaneous')->middleware('permission:stok-miscellaneous.view');

           // Rincian varian satu produk (khusus kategori yang punya varian, mis. Pakaian)
           Route::get('/pakaian/{product}', [StockController::class, 'show'])->defaults('categorySlug', 'pakaian')
               ->name('pakaian.show')->middleware('permission:stok-pakaian.view');
       });
 
    // Tambah Produk (form pembelian dari sales) + Cek Paket (daftar, detail, edit, centang datang)
    Route::controller(PurchaseController::class)
        ->name('purchases.')
        ->group(function () {
            Route::get('/stok/tambah', 'create')->name('create')->middleware('permission:produk-tambah.view');
            Route::post('/stok/tambah', 'store')->name('store')->middleware('permission:produk-tambah.create');
 
            Route::prefix('cek-paket')->group(function () {
                Route::get('/', 'index')->name('index')->middleware('permission:cek-paket.view');
                Route::get('/{purchase}', 'show')->name('show')->middleware('permission:cek-paket.view');
                Route::get('/{purchase}/edit', 'edit')->name('edit')->middleware('permission:cek-paket.edit');
                Route::put('/{purchase}', 'update')->name('update')->middleware('permission:cek-paket.edit');
                Route::delete('/{purchase}', 'destroy')->name('destroy')->middleware('permission:cek-paket.delete');
                Route::patch('/{purchase}/items/{item}/receive', 'receive')->name('receive')->middleware('permission:cek-paket.receive');
            });
        });
    /*
    |----------------------------------------------------------------------
    | Penjualan -> modul "penjualan-pembayaran" dan "penjualan-terjual"
    |----------------------------------------------------------------------
    | Pembayaran = form kasir (buat transaksi + kurangi stok).
    | Terjual    = daftar transaksi. Detail dibuka lewat modal (fetch ke route show),
    |              Edit di halaman penuh, Hapus lewat modal konfirmasi.
    | Create transaksi mengikuti permission "penjualan-pembayaran.create".
    */
    Route::prefix('penjualan')
        ->name('penjualan.')
        ->group(function () {

            Route::controller(PembayaranController::class)
                ->prefix('pembayaran')
                ->name('pembayaran.')
                ->group(function () {
                    Route::get('/', 'create')->name('create')->middleware('permission:penjualan-pembayaran.view');
                    Route::post('/', 'store')->name('store')->middleware('permission:penjualan-pembayaran.create');
                });

            Route::controller(TerjualController::class)
                ->prefix('terjual')
                ->name('terjual.')
                ->group(function () {
                    Route::get('/', 'index')->name('index')->middleware('permission:penjualan-terjual.view');
                    Route::get('/{terjual}', 'show')->name('show')->middleware('permission:penjualan-terjual.view');
                    Route::get('/{terjual}/edit', 'edit')->name('edit')->middleware('permission:penjualan-terjual.edit');
                    Route::put('/{terjual}', 'update')->name('update')->middleware('permission:penjualan-terjual.edit');
                    Route::delete('/{terjual}', 'destroy')->name('destroy')->middleware('permission:penjualan-terjual.delete');
                });
        });

     /*
    |----------------------------------------------------------------------
    | Laporan -> modul "laporan-keuangan", "laporan-penjualan", "laporan-stok"
    |----------------------------------------------------------------------
    | Semua laporan read-only. view = melihat, export = Excel dan PDF.
    | Laporan Penjualan dan Laporan Stok ditambahkan ke grup ini pada tahap berikutnya.
    */
    Route::prefix('laporan')
        ->name('laporan.')
        ->group(function () {
 
            Route::controller(LaporanKeuanganController::class)
                ->prefix('keuangan')
                ->name('keuangan.')
                ->group(function () {
                    Route::get('/', 'index')->name('index')->middleware('permission:laporan-keuangan.view');
                    Route::get('/excel', 'excel')->name('excel')->middleware('permission:laporan-keuangan.export');
                    Route::get('/pdf', 'pdf')->name('pdf')->middleware('permission:laporan-keuangan.export');
                });
        });

    // Management -> modul "management-*" (nama permission lama dipertahankan)
    Route::controller(RoleController::class)
        ->prefix('roles')
        ->name('roles.')
        ->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:role.read');
            Route::get('/create', 'create')->name('create')->middleware('permission:role.create');
            Route::post('/', 'store')->name('store')->middleware('permission:role.create');
            Route::get('/{role}/edit', 'edit')->name('edit')->middleware('permission:role.update');
            Route::put('/{role}', 'update')->name('update')->middleware('permission:role.update');
            Route::delete('/{role}', 'destroy')->name('destroy')->middleware('permission:role.delete');
            Route::get('/{role}/permissions', 'permissions')->name('permissions')->middleware('permission:role.permissions');
            Route::put('/{role}/permissions', 'updatePermissions')->name('permissions.update')->middleware('permission:role.permissions');
        });

    Route::controller(UserController::class)
        ->prefix('users')
        ->name('users.')
        ->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:user.read');
            Route::get('/create', 'create')->name('create')->middleware('permission:user.create');
            Route::post('/', 'store')->name('store')->middleware('permission:user.create');
            Route::get('/{user}/edit', 'edit')->name('edit')->middleware('permission:user.update');
            Route::put('/{user}', 'update')->name('update')->middleware('permission:user.update');
            Route::delete('/{user}', 'destroy')->name('destroy')->middleware('permission:user.delete');
        });

    Route::get('/permissions', [PermissionController::class, 'index'])
        ->name('permissions.index')
        ->middleware('permission:permission.read');
});
