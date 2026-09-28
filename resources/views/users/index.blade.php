@extends('layouts.app')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title">Daftar User</h3>
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                <i class="ki-outline ki-plus fs-5 me-1"></i> Tambah User
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
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-end">Aksi</th>
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
                                <td class="text-end">
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-icon btn-sm btn-light-warning me-1" title="Edit">
                                        <i class="ki-outline ki-pencil fs-5"></i>
                                    </a>
                                    @if ($user->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Yakin mau hapus user ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Hapus">
                                                <i class="ki-outline ki-trash fs-5"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-6">Belum ada user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection