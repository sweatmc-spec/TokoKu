@extends('layouts.app')

@section('title', 'Edit Role')
@section('page-title', 'Edit Role')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-5">
                    <label class="form-label required">Nama Role</label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control" required>
                    @error('name')
                        <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-5">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('roles.index') }}" class="btn btn-light">Batal</a>
            </form>
        </div>
    </div>
@endsection