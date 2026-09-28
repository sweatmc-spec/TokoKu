@extends('layouts.app')

@section('title', 'Role Management')
@section('page-title', 'Role Management')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title">Daftar Role</h3>
            <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                <i class="ki-outline ki-plus fs-5 me-1"></i> Tambah Role
            </a>
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
                            <th class="text-end">Aksi</th>
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
                                <td class="text-end">
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-icon btn-sm btn-light-warning me-1" title="Edit">
                                        <i class="ki-outline ki-pencil fs-5"></i>
                                    </a>
                                    <a href="{{ route('roles.permissions', $role) }}" class="btn btn-icon btn-sm btn-light-success me-1" title="Hak Akses">
                                        <i class="ki-outline ki-setting-3 fs-5"></i>
                                    </a>
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Yakin mau hapus role ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Hapus">
                                            <i class="ki-outline ki-trash fs-5"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-6">Belum ada role.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection