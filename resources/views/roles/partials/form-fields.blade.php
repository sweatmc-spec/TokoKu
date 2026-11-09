{{--
    Field form role, dipakai oleh modal Tambah dan modal Edit.

    Variabel:
    - $failed : true kalau modal ini dibuka ulang otomatis setelah validasi gagal
--}}
<div class="mb-5">
    <label class="form-label required">Nama Role</label>
    <input type="text" name="name" class="form-control" value="{{ $failed ? old('name') : '' }}" required>
    @if ($failed)
        @error('name')<div class="text-danger fs-7 mt-1 js-error">{{ $message }}</div>@enderror
    @endif
</div>

<div class="mb-2">
    <label class="form-label">Deskripsi</label>
    <textarea name="description" class="form-control" rows="3">{{ $failed ? old('description') : '' }}</textarea>
    @if ($failed)
        @error('description')<div class="text-danger fs-7 mt-1 js-error">{{ $message }}</div>@enderror
    @endif
</div>