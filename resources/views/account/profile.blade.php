@extends('layouts.app') {{-- sesuaikan dengan nama layout utama project Anda --}}

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Profile</h3>
    </div>

    <div class="card-body">
        @if (session('status') === 'profile-updated')
            <div class="alert alert-success">Profil berhasil diperbarui.</div>
        @endif
        @if (session('status') === 'avatar-removed')
            <div class="alert alert-success">Foto profil berhasil dihapus.</div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!--begin::Avatar-->
            <div class="d-flex align-items-center mb-8">
                <div class="symbol symbol-100px symbol-circle me-6">
                    <img id="avatar-preview" src="{{ $user->avatar_url }}" alt="{{ $user->name }}" />
                </div>
                <div>
                    <label for="avatar" class="btn btn-sm btn-light-primary mb-0">Ganti Foto</label>
                    <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp" class="d-none"
                           onchange="document.getElementById('avatar-preview').src = window.URL.createObjectURL(this.files[0])">

                    @if ($user->avatar)
                        <button type="button" class="btn btn-sm btn-color-danger btn-active-light-danger mb-0"
                                onclick="document.getElementById('delete-avatar-form').submit();">
                            Hapus Foto
                        </button>
                    @endif

                    <div class="text-muted fs-7 mt-1">JPG, PNG, atau WEBP. Maksimal 2MB.</div>
                    @error('avatar')
                        <div class="text-danger fs-7">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <!--end::Avatar-->

            <!--begin::Name-->
            <div class="mb-6">
                <label for="name" class="form-label required">Nama</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <!--end::Name-->

            <!--begin::Username-->
            <div class="mb-6">
                <label for="username" class="form-label required">Username</label>
                <input type="text" id="username" name="username" class="form-control @error('username') is-invalid @enderror"
                       value="{{ old('username', $user->username) }}" required>
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <!--end::Username-->

            <!--begin::Email (read-only, ikut akun login)-->
            <div class="mb-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" class="form-control" value="{{ $user->email }}" disabled>
                <div class="text-muted fs-7">Email mengikuti akun login Anda dan tidak diubah dari halaman ini.</div>
            </div>
            <!--end::Email-->

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>

        {{-- Form terpisah khusus untuk hapus foto, agar tidak nested di dalam form utama --}}
        <form id="delete-avatar-form" action="{{ route('profile.avatar.destroy') }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>
@endsection