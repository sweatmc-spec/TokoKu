@extends('layouts.app')

@section('title', 'Akses Role')
@section('page-title', 'Akses Role: ' . ucfirst($role->name))

@section('content')

{{-- ============================================================
     Style khusus halaman Akses Role.

     PENTING: blok ini HANYA berisi aturan ukuran, spacing, dan layout
     (font-size, padding, width/height, border-radius, flex/gap).
     TIDAK ADA warna hardcode di sini — semua warna (background, teks,
     border) diatur lewat class Metronic langsung di HTML (bg-light-*,
     text-*, text-gray-*, text-muted, badge-light-*, border-gray-*,
     card/card-header/card-body, form-check-custom form-check-solid),
     supaya otomatis mengikuti toggle mode terang/gelap Metronic.
     Dua-duanya pun cuma pakai rgba(var(--bs-primary-rgb), ...) yang
     merupakan variabel warna resmi Bootstrap/Metronic, bukan warna
     sembarangan.
============================================================ --}}
<style>
    .pg-wrap { display: flex; flex-direction: column; }

    /* Header hero card */
    .pg-header-card { position: relative; overflow: hidden; border-radius: 1rem; }
    .pg-header-card-body { position: relative; padding: 2rem; }
    .pg-header-glow {
        position: absolute; top: -70px; right: -70px; width: 220px; height: 220px;
        background: rgba(var(--bs-primary-rgb), .10);
        border-radius: 50%; filter: blur(60px); pointer-events: none;
    }
    .pg-header-info { max-width: 640px; }
    .pg-header-title { font-size: 1.75rem; }
    .pg-header-desc { font-size: 0.95rem; line-height: 1.6; }
    .pg-badge-count-total { font-size: 0.8rem; padding: .5rem 1rem; }
    .pg-header-note { font-size: 0.825rem; }
    .pg-link { font-size: 0.825rem; }
    .pg-btn { font-size: 0.95rem; padding: .75rem 1.25rem; }

    /* Daftar modul */
    .pg-module-list { display: flex; flex-direction: column; gap: 1.75rem; margin-top: 1.75rem; }
    .pg-module-card { border-radius: 1rem; }
    .pg-module-card-header { padding: 1.5rem 1.75rem; }
    .pg-module-card-body { padding: .5rem 1.75rem 1.5rem; }

    .pg-icon-badge {
        width: 3.5rem; height: 3.5rem;
        border-radius: 0.9rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .pg-icon-badge i { font-size: 1.65rem; line-height: 1; }
    .pg-module-title { font-size: 1.3rem; }
    .pg-module-badge-count { font-size: 0.8rem; padding: .45rem .9rem; }

    .pg-switch-label { font-size: 0.85rem; }
    .pg-switch-label-sm { font-size: 0.8rem; }

    /* Perbesar toggle switch bawaan Metronic (cuma ukuran, warnanya tetap dari Metronic) */
    .pg-switch-lg .form-check-input { width: 3.25rem !important; height: 1.65rem !important; }
    .pg-switch-sm .form-check-input { width: 2.5rem !important; height: 1.3rem !important; }

    .pg-perm-row { padding-top: 1.25rem; padding-bottom: 1.25rem; }
    .pg-perm-title { font-size: 1.05rem; }
    .pg-perm-tag { font-size: 0.7rem; padding: .3rem .65rem; letter-spacing: .03em; }
    .pg-perm-desc { font-size: 0.875rem; }

    .pg-subgroup { padding: 1.5rem; }
    .pg-subgroup-badge { font-size: 0.75rem; padding: .45rem .9rem; letter-spacing: .04em; }
    .pg-subgroup-count { font-size: 0.8rem; }
    .pg-subgroup-list { display: flex; flex-direction: column; gap: 1.25rem; }

    .pg-footer-bar { position: sticky; bottom: 1rem; z-index: 10; border-radius: 1rem; }
    .pg-footer-card-body { padding: 1.25rem 1.75rem; }
    .pg-footer-note { font-size: 0.875rem; }
    .pg-btn-sm { font-size: 0.85rem; padding: .6rem 1.1rem; }
</style>

@php
    // Kumpulkan semua nama permission (rekursif, termasuk children) untuk ringkasan di header.
    $collectPermissionNames = function ($mod) use (&$collectPermissionNames) {
        $names = $mod->permissions->pluck('name')->all();
        foreach ($mod->children as $child) {
            $names = array_merge($names, $collectPermissionNames($child));
        }
        return $names;
    };

    $allPermissionNames = collect($modules)
        ->flatMap(fn ($mod) => $collectPermissionNames($mod))
        ->unique()
        ->values();

    $totalPermissionCount   = $allPermissionNames->count();
    $grantedPermissionCount = $allPermissionNames->intersect($rolePermissions)->count();

    // Warna aksen per modul — HANYA nama warna semantik Bootstrap/Metronic yang valid,
    // supaya class bg-light-{accent}/text-{accent}/badge-light-{accent} otomatis ikut tema.
    $accentPalette = ['primary', 'warning', 'success', 'info', 'danger'];
@endphp

<form action="{{ route('roles.permissions.update', $role) }}" method="POST" class="pg-wrap">
    @csrf
    @method('PUT')

    {{-- ===== Header card ===== --}}
    <div class="card pg-header-card shadow-sm">
        <div class="pg-header-glow"></div>
        <div class="card-body pg-header-card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-4">
                <div class="pg-header-info">
                    <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
                        <h1 class="pg-header-title fw-bold text-gray-900 mb-0">
                            Akses Role: <span class="text-primary">{{ ucfirst($role->name) }}</span>
                        </h1>
                        <span class="badge badge-light-secondary pg-badge-count-total">
                            {{ $grantedPermissionCount }} / {{ $totalPermissionCount }} Izin Diberikan
                        </span>
                    </div>
                    <p class="text-muted pg-header-desc mb-0">
                        Kelola hak akses dan izin eksekusi fitur untuk pengguna dengan role <strong class="text-gray-700">{{ ucfirst($role->name) }}</strong>.
                    </p>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <button type="submit" class="btn btn-primary pg-btn">
                        <i class="ki-outline ki-check fs-3"></i>
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-light pg-btn">Batal</a>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pg-header-bottom mt-6 pt-5 border-top border-gray-300">
                <div class="d-flex align-items-center gap-2 text-muted pg-header-note">
                    <span class="bullet bullet-dot bg-primary"></span>
                    Perubahan hak akses akan diterapkan ke seluruh akun dengan role ini saat disimpan.
                </div>
                <div class="d-flex align-items-center gap-4">
                    <button type="button" class="btn btn-link p-0 text-primary fw-semibold pg-link" id="pg-select-all-modules">Aktifkan Semua Modul</button>
                    <span class="text-gray-400">|</span>
                    <button type="button" class="btn btn-link p-0 text-muted fw-semibold pg-link" id="pg-reset-changes">Batalkan Perubahan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Daftar modul ===== --}}
    <div class="pg-module-list">
        @foreach ($modules as $module)
            @include('roles.partials.permission-group', [
                'module'          => $module,
                'rolePermissions' => $rolePermissions,
                'accent'          => $accentPalette[$loop->index % count($accentPalette)],
            ])
        @endforeach
    </div>

    {{-- ===== Sticky footer bar ===== --}}
    <div class="card pg-footer-bar shadow-sm mt-6">
        <div class="card-body pg-footer-card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 text-muted pg-footer-note">
                <i class="ki-outline ki-check-circle text-success fs-3"></i>
                <span>Pastikan perubahan disimpan sebelum meninggalkan halaman ini.</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('roles.index') }}" class="btn btn-light pg-btn-sm">Batalkan</a>
                <button type="submit" class="btn btn-primary pg-btn-sm">
                    <i class="ki-outline ki-check fs-4"></i>
                    Simpan Konfigurasi Role
                </button>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    // "Pilih Semua" mencentang/mencabut semua switch di dalam grupnya,
    // termasuk grup bersarang di dalamnya (karena secara DOM ada di dalam container yang sama).
    document.querySelectorAll('.select-all-toggle').forEach(function (toggle) {
        const container = toggle.closest('[data-permission-group]');
        const switches = container.querySelectorAll('.permission-switch');

        const syncState = function () {
            toggle.checked = switches.length > 0 && Array.from(switches).every(function (s) { return s.checked; });
        };
        syncState();

        toggle.addEventListener('change', function () {
            switches.forEach(function (sw) { sw.checked = toggle.checked; });
        });

        switches.forEach(function (sw) {
            sw.addEventListener('change', syncState);
        });
    });

    // "Aktifkan Semua Modul" di header — centang semua switch di seluruh halaman.
    const selectAllModulesBtn = document.getElementById('pg-select-all-modules');
    if (selectAllModulesBtn) {
        selectAllModulesBtn.addEventListener('click', function () {
            document.querySelectorAll('.permission-switch, .select-all-toggle').forEach(function (el) {
                el.checked = true;
            });
        });
    }

    // "Batalkan Perubahan" — reload halaman supaya kembali ke state semula (belum disimpan).
    const resetBtn = document.getElementById('pg-reset-changes');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            window.location.reload();
        });
    }
</script>
@endpush