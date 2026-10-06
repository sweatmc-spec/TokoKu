{{-- Modal tambah / edit Pemasukan MANUAL. Satu modal untuk keduanya: tombol Edit membawa data-url + data-* --}}
<div class="modal fade" id="modal-pemasukan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="form-pemasukan" novalidate autocomplete="off">
                @csrf
                <div class="modal-header">
                    <h3 class="modal-title fw-bold" id="pm-title">Tambah pemasukan</h3>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="pm-error" role="alert"></div>

                    <div class="mb-5">
                        <label class="form-label required fw-semibold">Tanggal</label>
                        <input type="date" name="tanggal" id="pm-tanggal" class="form-control form-control-solid"
                               max="{{ now()->toDateString() }}" required>
                    </div>

                    <div class="mb-5">
                        <label class="form-label required fw-semibold">Keterangan</label>
                        <input type="text" name="keterangan" id="pm-keterangan" class="form-control form-control-solid"
                               maxlength="255" placeholder="Mis. Tambahan modal, pendapatan lain..." required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label required fw-semibold">Nominal</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="nominal" id="pm-nominal" inputmode="numeric"
                                   class="form-control form-control-solid text-end fw-bold" placeholder="0" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="pm-save">
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
    const modalEl  = document.getElementById('modal-pemasukan');
    const $form    = $('#form-pemasukan');
    const $err     = $('#pm-error');
    const $save    = $('#pm-save');
    const storeUrl = @json(route('keuangan.pemasukan.store'));
    const token    = @json(csrf_token());
    const today    = @json(now()->toDateString());
    const nfInt    = new Intl.NumberFormat('id-ID');

    let action = storeUrl;
    let isEdit = false;

    const digits = s => String(s || '').replace(/\D/g, '');
    const money  = s => { const d = digits(s); return d ? nfInt.format(parseInt(d, 10)) : ''; };

    function showError(messages) {
        const $ul = $('<ul class="mb-0 ps-5"></ul>');
        messages.forEach(m => $ul.append($('<li></li>').text(m)));
        $err.removeClass('d-none').empty().append($ul);
    }

    // input nominal tampil dengan pemisah ribuan; server mengambil angkanya saja
    $('#pm-nominal').on('input', function () { this.value = money(this.value); });

    modalEl.addEventListener('show.bs.modal', function (e) {
        const b = e.relatedTarget;                       // tombol yang membuka modal
        isEdit  = !!(b && b.dataset.url);
        action  = isEdit ? b.dataset.url : storeUrl;

        $('#pm-title').text(isEdit ? 'Edit pemasukan' : 'Tambah pemasukan');
        $err.addClass('d-none').empty();
        $form[0].reset();

        $('#pm-tanggal').val(isEdit ? b.dataset.tanggal : today);
        $('#pm-keterangan').val(isEdit ? b.dataset.keterangan : '');
        $('#pm-nominal').val(isEdit ? money(b.dataset.nominal) : '');
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
            window.location.reload();                    // pesan sukses ada di flash session
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
