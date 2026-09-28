@extends('layouts.app')

@section('title', 'Tambah User')
@section('page-title', 'Tambah User')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf

                <div class="mb-5">
                    <label class="form-label required">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                    @error('name')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="mb-5">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                    @error('email')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="mb-5">
                    <label class="form-label required">Password</label>
                    <input type="password" name="password" class="form-control" required>
                    @error('password')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="mb-5">
                    <label class="form-label required">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="">Pilih role...</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('users.index') }}" class="btn btn-light">Batal</a>
            </form>
        </div>
    </div>
@endsection