{{--
    Field form user, dipakai oleh modal Tambah dan modal Edit.

    Variabel:
    - $mode   : 'create' atau 'edit'
    - $failed : true kalau modal ini dibuka ulang otomatis setelah validasi gagal
                (nilai lama + pesan error dari server ditampilkan)
    - $roles  : daftar role untuk dropdown
--}}
<div class="mb-5">
    <label class="form-label required">Nama</label>
    <input type="text" name="name" class="form-control" value="{{ $failed ? old('name') : '' }}" required>
    @if ($failed)
        @error('name')<div class="text-danger fs-7 mt-1 js-error">{{ $message }}</div>@enderror
    @endif
</div>

<div class="mb-5">
    <label class="form-label required">Email</label>
    <input type="email" name="email" class="form-control" value="{{ $failed ? old('email') : '' }}" required>
    @if ($failed)
        @error('email')<div class="text-danger fs-7 mt-1 js-error">{{ $message }}</div>@enderror
    @endif
</div>

<div class="mb-5">
    <label class="form-label {{ $mode === 'create' ? 'required' : '' }}">
        {{ $mode === 'create' ? 'Password' : 'Password baru' }}
    </label>
    <input type="password" name="password" class="form-control" autocomplete="new-password"
           placeholder="{{ $mode === 'edit' ? 'Kosongkan kalau tidak diubah' : '' }}"
           {{ $mode === 'create' ? 'required' : '' }}>
    @if ($failed)
        @error('password')<div class="text-danger fs-7 mt-1 js-error">{{ $message }}</div>@enderror
    @endif
</div>

<div class="mb-2">
    <label class="form-label required">Role</label>
    <select name="role" class="form-select" required>
        <option value="">Pilih role...</option>
        @foreach ($roles as $role)
            <option value="{{ $role->name }}" {{ $failed && old('role') === $role->name ? 'selected' : '' }}>
                {{ ucfirst($role->name) }}
            </option>
        @endforeach
    </select>
    @if ($failed)
        @error('role')<div class="text-danger fs-7 mt-1 js-error">{{ $message }}</div>@enderror
    @endif
</div>