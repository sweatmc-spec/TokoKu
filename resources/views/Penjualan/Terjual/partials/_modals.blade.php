{{-- Modal Detail (isi dimuat lewat fetch dari route terjual.show) + modal Hapus --}}

<div class="modal fade" id="modal-view-terjual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fw-bold" id="view-title">Detail transaksi</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Tutup">
                    <i class="ki-outline ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body px-lg-10 py-8" id="view-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-delete-terjual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-450px">
        <form method="POST" id="form-delete-terjual" action="#" class="modal-content">
            @csrf
            @method('DELETE')

            <div class="modal-body text-center px-10 py-10">
                <i class="ki-duotone ki-trash fs-5x text-danger mb-5">
                    <span class="path1"></span><span class="path2"></span>
                    <span class="path3"></span><span class="path4"></span>
                    <span class="path5"></span>
                </i>

                <h3 class="fw-bold text-gray-900 mb-2">Hapus Transaksi</h3>
                <div class="text-gray-600 fs-6 mb-2">
                    Hapus transaksi <span class="fw-bold text-gray-900" id="del-code"></span>
                    atas nama <span class="fw-bold text-gray-900" id="del-customer"></span>
                    senilai <span class="fw-bold text-gray-900" id="del-total"></span>?
                </div>
                <div class="text-muted fs-7 mb-8">
                    Stok semua barang di transaksi ini akan dikembalikan. Tindakan ini tidak bisa dibatalkan.
                </div>

                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger" id="btn-delete-terjual">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    // ----- Detail -----
    const viewModal = document.getElementById('modal-view-terjual');
    viewModal.addEventListener('show.bs.modal', async function (e) {
        const btn  = e.relatedTarget;
        const body = document.getElementById('view-body');
        document.getElementById('view-title').textContent = 'Detail ' + (btn.dataset.code || 'transaksi');
        body.innerHTML = '<div class="text-center py-10"><span class="spinner-border text-primary"></span></div>';

        try {
            const res = await fetch(btn.dataset.url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error(res.status);
            body.innerHTML = await res.text();
        } catch (err) {
            body.innerHTML = '<div class="text-danger text-center py-10">Detail transaksi gagal dimuat. Tutup lalu coba lagi.</div>';
        }
    });

    // ----- Hapus -----
    const delModal = document.getElementById('modal-delete-terjual');
    const delForm  = document.getElementById('form-delete-terjual');
    delModal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        delForm.action = btn.dataset.action;
        document.getElementById('del-code').textContent     = btn.dataset.code;
        document.getElementById('del-customer').textContent = btn.dataset.customer;
        document.getElementById('del-total').textContent    = btn.dataset.total;
        document.getElementById('btn-delete-terjual').disabled = false;
    });
    // cegah klik ganda tanpa menghalangi submit
    delForm.addEventListener('submit', function () {
        setTimeout(() => { document.getElementById('btn-delete-terjual').disabled = true; }, 0);
    });
})();
</script>
@endpush