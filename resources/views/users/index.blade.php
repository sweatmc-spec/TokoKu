@extends('layouts.app')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
@php
    // Kalau controller belum kirim $roles, ambil sendiri di sini.
    $roles = $roles ?? \Spatie\Permission\Models\Role::orderBy('name')->get();

    // Setelah validasi gagal, modal yang tadi disubmit dibuka lagi otomatis
    // lengkap dengan nilai lama dan pesan error-nya.
    // Kolom Aksi cuma ditampilkan kalau user boleh mengubah atau menghapus.
    $showActions = auth()->user()->canAny(['user.update', 'user.delete']);

    $formType   = old('_form');
    $createFail = $errors->any() && $formType === 'create';
    $editFail   = $errors->any() && $formType === 'edit';
@endphp

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Daftar User</h3>
        @can('user.create')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUserCreate">
                <i class="ki-outline ki-plus fs-5 me-1"></i> Tambah User
            </button>
        @endcan
    </div>
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted">
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        @if ($showActions)
                            <th class="text-end">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td class="text-muted">{{ $user->email }}</td>
                            <td>
                                <span class="badge badge-light-primary text-capitalize">
                                    {{ $user->roles->first()?->name ?? '-' }}
                                </span>
                            </td>
                            @if ($showActions)
                            <td class="text-end">
                                @can('user.update')
                                <button type="button" class="btn btn-icon btn-sm btn-light-warning me-1" title="Edit"
                                        data-bs-toggle="modal" data-bs-target="#modalUserEdit"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}"
                                        data-email="{{ $user->email }}"
                                        data-role="{{ $user->roles->first()?->name }}"
                                        data-update-url="{{ route('users.update', $user) }}">
                                    <i class="ki-outline ki-pencil fs-5"></i>
                                </button>
                                @endcan

                                @can('user.delete')
                                @if ($user->id !== auth()->id())
                                    <button type="button" class="btn btn-icon btn-sm btn-light-danger" title="Hapus"
                                            data-bs-toggle="modal" data-bs-target="#modalDelete"
                                            data-delete-url="{{ route('users.destroy', $user) }}"
                                            data-delete-type="user"
                                            data-delete-name="{{ $user->name }}"
                                            data-delete-detail="{{ $user->email }}">
                                        <i class="ki-outline ki-trash fs-5"></i>
                                    </button>
                                @endif
                                @endcan
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showActions ? 4 : 3 }}" class="text-center text-muted py-6">Belum ada user.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('users.partials.modals', ['roles' => $roles, 'createFail' => $createFail, 'editFail' => $editFail])

@endsection