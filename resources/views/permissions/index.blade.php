@extends('layouts.app')

@section('title', 'Permission List')
@section('page-title', 'Permission List')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Permission</h3>
        </div>
        <div class="card-body">
            <p class="text-muted fs-7 mb-6">
                Permission dibuat otomatis lewat seeder untuk tiap modul (Create/Read/Update/Delete).
                Untuk mengatur permission mana yang aktif per role, buka halaman
                <a href="{{ route('roles.index') }}">Role Management</a> lalu klik tombol Hak Akses.
            </p>

            @foreach ($permissions as $feature => $items)
                <div class="mb-6">
                    <h4 class="fs-6 fw-bold mb-2">{{ $feature }}</h4>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($items as $permission)
                            <span class="badge badge-light-primary">{{ $permission->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection