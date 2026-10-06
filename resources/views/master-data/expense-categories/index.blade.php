@extends('layouts.app')

@section('title', 'Kategori Pengeluaran')

@section('content')
    @include('master-data.partials.flash')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-4 py-5">
            <div>
                <h4 class="card-title mb-1">Kategori Pengeluaran</h4>
                <div class="text-muted fs-7">Dipilih saat mencatat pengeluaran operasional di halaman Pengeluaran.</div>
            </div>

            @can('master-kategori-pengeluaran.create')
                <button type="button" class="btn btn-sm btn-primary text-nowrap d-inline-flex align-items-center gap-1"
                        data-bs-toggle="modal" data-bs-target="#modal-kategori">
                    <i class="ki-outline ki-plus fs-4"></i> Tambah Kategori
                </button>
            @endcan
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle mb-0">
                    <thead class="bg-body-tertiary">
                        <tr class="text-uppercase fs-8 fw-semibold text-muted">
                            <th class="ps-6" style="width:60px">No</th>
                            <th>Nama kategori</th>
                            <th class="text-center">Dipakai</th>
                            <th class="pe-6" style="width:130px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kategoris as $k)
                            <tr>
                                <td class="ps-6">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $k->nama }}</span>
                                    @if ($k->is_system)
                                        <span class="badge badge-light-warning ms-2">Bawaan</span>
                                        <div class="text-muted fs-8">Diisi otomatis dari Cek Paket. Tidak bisa diubah atau dihapus.</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($k->pengeluarans_count > 0)
                                        {{ number_format($k->pengeluarans_count, 0, ',', '.') }} pengeluaran
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="pe-6">
                                    @if ($k->is_system)
                                        <span class="text-muted fs-8 d-inline-flex align-items-center gap-1">
                                            <i class="ki-outline ki-lock fs-5"></i> Terkunci
                                        </span>
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            @can('master-kategori-pengeluaran.edit')
                                                <button type="button" title="Edit"
                                                        class="btn btn-sm btn-icon border-0 bg-warning-subtle text-warning-emphasis"
                                                        data-bs-toggle="modal" data-bs-target="#modal-kategori"
                                                        data-url="{{ route('master-data.expense-categories.update', $k) }}"
                                                        data-nama="{{ $k->nama }}">
                                                    <i class="ki-outline ki-pencil fs-4 text-warning"></i>
                                                </button>
                                            @endcan
                                            @can('master-kategori-pengeluaran.delete')
                                                <button type="button" title="Hapus"
                                                        class="btn btn-sm btn-icon border-0 bg-danger-subtle text-danger-emphasis"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-url="{{ route('master-data.expense-categories.destroy', $k) }}"
                                                        data-name="kategori pengeluaran &quot;{{ $k->nama }}&quot;">
                                                    <i class="ki-outline ki-trash fs-4 text-danger"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-8">Belum ada kategori pengeluaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal tambah / edit --}}
    @canany(['master-kategori-pengeluaran.create', 'master-kategori-pengeluaran.edit'])
        <div class="modal fade" id="modal-kategori" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="form-kategori" novalidate autocomplete="off">
                        @csrf
                        <div class="modal-header">
                            <h3 class="modal-title fw-bold" id="kt-title">Tambah kategori</h3>
                            <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                                <i class="ki-outline ki-cross fs-1"></i>
                            </div>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" id="kt-error" role="alert"></div>
                            <label class="form-label required fw-semibold">Nama kategori</label>
                            <input type="text" name="nama" id="kt-nama" class="form-control form-control-solid"
                                   maxlength="100" placeholder="Mis. Sewa, Perawatan, Konsumsi..." required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="kt-save">
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress">Menyimpan...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcanany

    @include('master-data.partials.delete-modal', ['label' => 'Kategori Pengeluaran'])
@endsection

@push('scripts')
    @include('master-data.partials.scripts')

    <script>
    (function () {
        const modalEl  = document.getElementById('modal-kategori');
        if (!modalEl) return;                            // user tanpa izin create / edit

        const $form    = $('#form-kategori');
        const $err     = $('#kt-error');
        const $save    = $('#kt-save');
        const storeUrl = @json(route('master-data.expense-categories.store'));
        const token    = @json(csrf_token());

        let action = storeUrl;
        let isEdit = false;

        function showError(messages) {
            const $ul = $('<ul class="mb-0 ps-5"></ul>');
            messages.forEach(m => $ul.append($('<li></li>').text(m)));
            $err.removeClass('d-none').empty().append($ul);
        }

        modalEl.addEventListener('show.bs.modal', function (e) {
            const b = e.relatedTarget;
            isEdit  = !!(b && b.dataset.url);
            action  = isEdit ? b.dataset.url : storeUrl;

            $('#kt-title').text(isEdit ? 'Edit kategori' : 'Tambah kategori');
            $err.addClass('d-none').empty();
            $('#kt-nama').val(isEdit ? b.dataset.nama : '');
        });

        $form.on('submit', function (ev) {
            ev.preventDefault();
            $err.addClass('d-none');
            $save.attr('data-kt-indicator', 'on').prop('disabled', true);

            $.ajax({
                url: action,
                method: 'POST',
                data: $form.serialize() + (isEdit ? '&_method=PUT' : ''),
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': token },
            }).done(function () {
                window.location.reload();                // pesan sukses ada di flash session
            }).fail(function (xhr) {
                $save.removeAttr('data-kt-indicator').prop('disabled', false);

                const json = xhr.responseJSON;
                if (xhr.status === 422 && json) {
                    showError(json.errors ? Object.values(json.errors).flat() : [json.message]);
                } else {
                    showError([(json && json.message) || 'Terjadi kesalahan. Silakan coba lagi.']);
                }
            });
        });
    })();
    </script>
@endpush
