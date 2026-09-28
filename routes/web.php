<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
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