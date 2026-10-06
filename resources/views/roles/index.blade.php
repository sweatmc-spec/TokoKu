@extends('layouts.app')

@section('title', 'Role Management')
@section('page-title', 'Role Management')

@section('content')
@php
    // Setelah validasi gagal, modal yang tadi disubmit dibuka lagi otomatis
    // lengkap dengan nilai lama dan pesan error-nya.
    // Kolom Aksi cuma ditampilkan kalau user boleh melakukan minimal satu aksi.
    $showActions = auth()->user()->canAny(['role.update', 'role.permissions', 'role.delete']);

    $formType   = old('_form');
    $createFail = $errors->any() && $formType === 'create';
    $editFail   = $errors->any() && $formType === 'edit';

    // Role bawaan sistem: tombol hapus tidak ditampilkan (controller juga menolaknya).
    $protectedRoles = ['admin', 'karyawan'];
@endphp

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Daftar Role</h3>
        @can('role.create')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRoleCreate">
                <i class="ki-outline ki-plus fs-5 me-1"></i> Tambah Role
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
                        <th>Deskripsi</th>
                        <th>Jumlah User</th>
                        <th>Hak Akses</th>
                        @if ($showActions)
                            <th class="text-end">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td class="fw-semibold text-capitalize">{{ $role->name }}</td>
                            <td class="text-muted">{{ $role->description ?? '-' }}</td>
                            <td>
                                <span class="badge badge-light-secondary">{{ $role->users_count }} User</span>
                            </td>
                            <td>
                                <span class="badge badge-light-primary">
                                    <i class="ki-outline ki-shield-tick fs-6 me-1"></i>
                                    {{ $role->permissions_count }}
                                </span>
                            </td>
                            @if ($showActions)
                            <td class="text-end">
                                @can('role.update')
                                <button type="button" class="btn btn-icon btn-sm btn-light-warning me-1" title="Edit"
                                        data-bs-toggle="modal" data-bs-target="#modalRoleEdit"
                                        data-id="{{ $role->id }}"
                                        data-name="{{ $role->name }}"
                                        data-description="{{ $role->description }}"
                                        data-update-url="{{ route('roles.update', $role) }}">
                                    <i class="ki-outline ki-pencil fs-5"></i>
                                </button>
                                @endcan

                                @can('role.permissions')
                                <a href="{{ route('roles.permissions', $role) }}" class="btn btn-icon btn-sm btn-light-success me-1" title="Hak Akses">
                                    <i class="ki-outline ki-setting-3 fs-5"></i>
                                </a>
                                @endcan

                                @can('role.delete')
                                @unless (in_array($role->name, $protectedRoles))
                                    <button type="button" class="btn btn-icon btn-sm btn-light-danger" title="Hapus"
                                            data-bs-toggle="modal" data-bs-target="#modalDelete"
                                            data-delete-url="{{ route('roles.destroy', $role) }}"
                                            data-delete-type="role"
                                            data-delete-name="{{ $role->name }}"
                                            data-delete-detail="{{ $role->description }}"
                                            data-delete-blocked="{{ $role->users_count > 0 ? "Role ini masih dipakai {$role->users_count} user. Pindahkan user-nya ke role lain dulu sebelum menghapus." : '' }}">
                                        <i class="ki-outline ki-trash fs-5"></i>
                                    </button>
                                @endunless
                                @endcan
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showActions ? 5 : 4 }}" class="text-center text-muted py-6">Belum ada role.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('roles.partials.modals', ['createFail' => $createFail, 'editFail' => $editFail])

@endsection