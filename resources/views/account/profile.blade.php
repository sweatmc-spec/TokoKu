@extends('layouts.app') {{-- sesuaikan dengan nama layout utama project Anda --}}

@section('title', 'Profil Saya')

@section('content')
@php
    // Status karyawan mengikuti role (Spatie Permission). Kosong kalau user belum punya role.
    $roleName = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() : null;
@endphp

<div class="mb-7">
    <h2 class="fw-bold mb-1">Edit Profil Akun</h2>
    <div class="text-muted fs-7">Perbarui foto profil dan tinjau informasi data pribadi Anda.</div>
</div>

@if (session('status') === 'profile-updated')
    <div class="alert alert-success">Profil berhasil diperbarui.</div>
@endif
@if (session('status') === 'avatar-removed')
    <div class="alert alert-success">Foto profil berhasil dihapus.</div>
@endif

<form id="profile-form" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-5">

        {{-- FOTO PROFIL --}}
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Foto Profil</h3>
                </div>

                <div class="card-body">
                    <div class="ratio ratio-1x1 rounded overflow-hidden bg-secondary mb-5">
                        <img id="avatar-preview" style="object-fit:cover"
                             @if ($user->avatar)
                                 src="{{ route('profile.avatar.show', ['user' => $user, 'v' => $user->updated_at->timestamp]) }}"
                             @else
                                 class="d-none"
                             @endif
                             alt="{{ $user->name }}">

                        <div id="avatar-placeholder"
                             class="d-flex flex-column align-items-center justify-content-center text-gray-500 {{ $user->avatar ? 'd-none' : '' }}">
                            <i class="ki-duotone ki-profile-circle fs-5x mb-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <span class="fs-7">Belum ada foto</span>
                        </div>
                    </div>

                    <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp" class="d-none">

                    <div class="d-flex gap-3 mb-4">
                        <label for="avatar" class="btn btn-light-primary flex-grow-1 mb-0">
                            <i class="ki-duotone ki-picture fs-4">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Ganti Foto
                        </label>

                        @if ($user->avatar)
                            <button type="button" class="btn btn-light-danger flex-grow-1"
                                    onclick="document.getElementById('delete-avatar-form').submit();">
                                <i class="ki-duotone ki-trash fs-4">
                                    <span class="path1"></span><span class="path2"></span>
                                    <span class="path3"></span><span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                                Hapus Foto
                            </button>
                        @endif
                    </div>

                    <div class="text-muted fs-7">JPG, PNG, atau WEBP. Maksimal 2MB.</div>
                    <div id="avatar-hint" class="text-primary fs-7 mt-1 d-none">Klik "Simpan Perubahan" untuk menyimpan foto baru.</div>

                    {{-- Error dari browser (file terlalu besar, dicek sebelum submit) --}}
                    <div id="avatar-error" class="text-danger fs-7 mt-1 d-none"></div>

                    {{-- Error dari server (422) --}}
                    @error('avatar')
                        <div id="avatar-server-error" class="text-danger fs-7 mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- DATA PRIBADI --}}
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Data Pribadi</h3>
                </div>

                <div class="card-body">
                    {{-- Nama --}}
                    <div class="row mb-7">
                        <label for="name" class="col-lg-4 col-form-label required fw-semibold fs-6">Nama Lengkap</label>
                        <div class="col-lg-8">
                            <input type="text" id="name" name="name"
                                   class="form-control form-control-solid @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Email (terkunci, ikut akun login) --}}
                    <div class="row mb-7">
                        <label for="email" class="col-lg-4 col-form-label fw-semibold fs-6">Email</label>
                        <div class="col-lg-8">
                            <div class="input-group">
                                <input type="email" id="email" class="form-control form-control-solid"
                                       value="{{ $user->email }}" disabled>
                                <span class="input-group-text">
                                    <i class="ki-duotone ki-lock-2 fs-3 text-gray-500">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        <span class="path4"></span><span class="path5"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="text-muted fs-7 mt-1">Mengikuti akun login Anda dan tidak bisa diubah.</div>
                        </div>
                    </div>

                    {{-- Status karyawan (terkunci, ikut role) --}}
                    <div class="row mb-7">
                        <label for="status" class="col-lg-4 col-form-label fw-semibold fs-6">Status Karyawan</label>
                        <div class="col-lg-8">
                            <div class="input-group">
                                <input type="text" id="status" class="form-control form-control-solid"
                                       value="{{ $roleName ? \Illuminate\Support\Str::headline($roleName) : '-' }}" disabled>
                                <span class="input-group-text">
                                    <i class="ki-duotone ki-lock-2 fs-3 text-gray-500">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        <span class="path4"></span><span class="path5"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="text-muted fs-7 mt-1">Mengikuti role akun Anda.</div>
                        </div>
                    </div>

                    {{-- Tanggal terdaftar (terkunci, tetap sejak akun dibuat) --}}
                    <div class="row mb-0">
                        <label for="registered" class="col-lg-4 col-form-label fw-semibold fs-6">Tanggal Terdaftar</label>
                        <div class="col-lg-8">
                            <div class="input-group">
                                <input type="text" id="registered" class="form-control form-control-solid"
                                       value="{{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-' }}" disabled>
                                <span class="input-group-text">
                                    <i class="ki-duotone ki-lock-2 fs-3 text-gray-500">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        <span class="path4"></span><span class="path5"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="text-muted fs-7 mt-1">Tercatat saat akun pertama kali dibuat dan tidak bisa diubah.</div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>

                    <button type="submit" form="logout-form" class="btn btn-light-danger">
                        <i class="ki-duotone ki-exit-right fs-4">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Logout
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>

{{-- Form terpisah (tidak nested di form utama) --}}
<form id="delete-avatar-form" action="{{ route('profile.avatar.destroy') }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const MAX_AVATAR_MB = 2; // samakan dengan 'max:2048' di ProfileController@update
    const maxBytes = MAX_AVATAR_MB * 1024 * 1024;

    const form        = document.getElementById('profile-form');
    const input       = document.getElementById('avatar');
    const preview     = document.getElementById('avatar-preview');
    const placeholder = document.getElementById('avatar-placeholder');
    const hint        = document.getElementById('avatar-hint');
    const errorBox    = document.getElementById('avatar-error');
    const serverError = document.getElementById('avatar-server-error'); // null kalau tidak ada error dari server

    const originalSrc = preview.getAttribute('src'); // null kalau user belum punya foto
    let objectUrl = null;

    function tampilkanError(teks) {
        errorBox.textContent = teks;
        errorBox.classList.toggle('d-none', ! teks);
    }

    function pesanTerlaluBesar(file) {
        return 'Ukuran foto ' + (file.size / 1024 / 1024).toFixed(1) + ' MB terlalu besar. Maksimal ' + MAX_AVATAR_MB + ' MB.';
    }

    // tampilkan foto, atau placeholder kalau tidak ada foto
    function setPreview(src) {
        if (src) {
            preview.src = src;
            preview.classList.remove('d-none');
            placeholder.classList.add('d-none');
        } else {
            preview.removeAttribute('src');
            preview.classList.add('d-none');
            placeholder.classList.remove('d-none');
        }
    }

    // kembalikan preview ke foto yang sedang tersimpan di server
    function pulihkanPreview() {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        setPreview(originalSrc);
        hint.classList.add('d-none');
    }

    input.addEventListener('change', function () {
        const file = this.files[0];

        if (serverError) serverError.classList.add('d-none');
        tampilkanError('');

        // dialog dibatalkan: tidak ada file dipilih
        if (! file) {
            pulihkanPreview();
            return;
        }

        if (file.size > maxBytes) {
            tampilkanError(pesanTerlaluBesar(file));
            this.value = ''; // kosongkan supaya tidak ikut terkirim
            pulihkanPreview();
            return;
        }

        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        setPreview(objectUrl);
        hint.classList.remove('d-none');
    });

    // jaring pengaman saat submit
    form.addEventListener('submit', function (e) {
        const file = input.files[0];

        if (file && file.size > maxBytes) {
            e.preventDefault();
            tampilkanError(pesanTerlaluBesar(file));
        }
    });
});
</script>
@endpush