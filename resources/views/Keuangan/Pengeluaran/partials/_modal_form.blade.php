{{-- Modal tambah / edit Pengeluaran OPERASIONAL. Satu modal untuk keduanya: tombol Edit membawa data-url + data-* --}}
<div class="modal fade" id="modal-pengeluaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="form-pengeluaran" novalidate autocomplete="off" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h3 class="modal-title fw-bold" id="pg-title">Tambah pengeluaran</h3>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">
                    {{-- semua error (validasi, ukuran file terlalu besar, dll.) muncul di sini --}}
                    <div class="alert alert-danger d-none" id="pg-error" role="alert"></div>

                    <div class="row g-5 mb-5">
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Tanggal</label>
                            <input type="date" name="tanggal" id="pg-tanggal" class="form-control form-control-solid"
                                   max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Kategori</label>
                            <select name="kategori_pengeluaran_id" id="pg-kategori" class="form-select form-select-solid" required>
                                <option></option>
                                @foreach ($kategoriManual as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                                @endforeach
                            </select>
                            @can('master-kategori-pengeluaran.view')
                                <div class="form-text">
                                    Kategori kurang? <a href="{{ route('master-data.expense-categories.index') }}">Kelola di Master Data</a>.
                                </div>
                            @endcan
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-semibold">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" id="pg-keterangan" class="form-control form-control-solid"
                               maxlength="255" placeholder="Mis. Listrik September, beli plastik 5 pak...">
                    </div>

                    <div class="mb-5">
                        <label class="form-label required fw-semibold">Nominal</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="nominal" id="pg-nominal" inputmode="numeric"
                                   class="form-control form-control-solid text-end fw-bold" placeholder="0" required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Bukti / foto nota <span class="text-muted fw-normal">(opsional, maks. 2 MB)</span></label>
                        <input type="file" name="bukti" id="pg-bukti" class="form-control form-control-solid"
                               accept="image/jpeg,image/png,image/webp">

                        {{-- hanya tampil saat edit dan pengeluaran itu sudah punya bukti --}}
                        <div class="d-none mt-3" id="pg-bukti-now">
                            <a href="#" target="_blank" rel="noopener" id="pg-bukti-link" class="fs-7">Lihat bukti saat ini</a>
                            <div class="form-check form-check-custom form-check-solid mt-2">
                                <input class="form-check-input" type="checkbox" name="hapus_bukti" value="1" id="pg-hapus-bukti">
                                <label class="form-check-label fs-7" for="pg-hapus-bukti">Hapus bukti ini</label>
                            </div>
                            <div class="form-text">Memilih file baru di atas akan menggantikan bukti saat ini.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="pg-save">
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress">Menyimpan...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const MAX_BYTES = 2 * 1024 * 1024;                  // sama dengan batas di PengeluaranRequest (2048 KB)
    const modalEl   = document.getElementById('modal-pengeluaran');
    const $form     = $('#form-pengeluaran');
    const $err      = $('#pg-error');
    const $save     = $('#pg-save');
    const $kategori = $('#pg-kategori');
    const storeUrl  = @json(route('keuangan.pengeluaran.store'));
    const token     = @json(csrf_token());
    const today     = @json(now()->toDateString());
    const nfInt     = new Intl.NumberFormat('id-ID');

    let action = storeUrl;
    let isEdit = false;

    const digits = s => String(s || '').replace(/\D/g, '');
    const money  = s => { const d = digits(s); return d ? nfInt.format(parseInt(d, 10)) : ''; };
    const mb     = bytes => (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB';

    function showError(messages) {
        const $ul = $('<ul class="mb-0 ps-5"></ul>');
        messages.forEach(m => $ul.append($('<li></li>').text(m)));
        $err.removeClass('d-none').empty().append($ul);
    }

    // select2 di dalam modal: dropdownParent wajib, kalau tidak dropdown tertutup / tidak bisa diketik
    $kategori.select2({
        dropdownParent: $(modalEl),
        width: '100%',
        placeholder: 'Pilih kategori',
    });

    $('#pg-nominal').on('input', function () { this.value = money(this.value); });

    // Cek ukuran SEBELUM diunggah: file terlalu besar ditolak langsung dengan alert
    $('#pg-bukti').on('change', function () {
        const file = this.files[0];

        if (file && file.size > MAX_BYTES) {
            showError(['Ukuran bukti ' + mb(file.size) + ' melebihi batas 2 MB. Pilih foto yang lebih kecil.']);
            this.value = '';
            return;
        }
        $err.addClass('d-none');
    });

    modalEl.addEventListener('show.bs.modal', function (e) {
        const b = e.relatedTarget;                       // tombol yang membuka modal
        isEdit  = !!(b && b.dataset.url);
        action  = isEdit ? b.dataset.url : storeUrl;

        $('#pg-title').text(isEdit ? 'Edit pengeluaran' : 'Tambah pengeluaran');
        $err.addClass('d-none').empty();
        $form[0].reset();

        $('#pg-tanggal').val(isEdit ? b.dataset.tanggal : today);
        $kategori.val(isEdit ? b.dataset.kategori : '').trigger('change');
        $('#pg-keterangan').val(isEdit ? b.dataset.keterangan : '');
        $('#pg-nominal').val(isEdit ? money(b.dataset.nominal) : '');

        const bukti = isEdit ? (b.dataset.bukti || '') : '';
        $('#pg-bukti-now').toggleClass('d-none', !bukti);
        $('#pg-bukti-link').attr('href', bukti || '#');
    });

    $form.on('submit', function (ev) {
        ev.preventDefault();
        $err.addClass('d-none');

        const file = $('#pg-bukti')[0].files[0];
        if (file && file.size > MAX_BYTES) {
            showError(['Ukuran bukti ' + mb(file.size) + ' melebihi batas 2 MB. Pilih foto yang lebih kecil.']);
            return;
        }

        const fd = new FormData($form[0]);               // sudah berisi _token dan file bukti
        if (isEdit) fd.append('_method', 'PUT');         // multipart tidak bisa memakai PUT langsung

        $save.attr('data-kt-indicator', 'on').prop('disabled', true);

        $.ajax({
            url: action,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': token },
        }).done(function () {
            window.location.reload();                    // pesan sukses ada di flash session
        }).fail(function (xhr) {
            $save.removeAttr('data-kt-indicator').prop('disabled', false);

            const json = xhr.responseJSON;

            if (xhr.status === 413) {                    // melebihi post_max_size di server
                showError(['File terlalu besar untuk diunggah. Bukti maksimal 2 MB.']);
            } else if (xhr.status === 422 && json) {
                showError(json.errors ? Object.values(json.errors).flat() : [json.message]);
            } else {
                showError([(json && json.message) || 'Terjadi kesalahan. Silakan coba lagi.']);
            }
        });
    });
})();
</script>
@endpush
