@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-5">
                    <label class="form-label required">Nama</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                    @error('name')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="mb-5">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                    @error('email')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="mb-5">
                    <label class="form-label">Password baru</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan kalau tidak diubah">
                    @error('password')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="mb-5">
                    <label class="form-label required">Role</label>
                    <select name="role" class="form-select" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('users.index') }}" class="btn btn-light">Batal</a>
            </form>
        </div>
    </div>
@endsection