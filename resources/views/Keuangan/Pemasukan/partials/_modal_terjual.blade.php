{{--
    Modal detail transaksi Terjual, dibuka dari kode transaksi di tabel Pemasukan.
    Isinya dimuat lewat fetch dari route penjualan.terjual.show (potongan HTML yang sama dengan
    modal di halaman Terjual), jadi tidak ada tampilan detail ganda yang harus dirawat.
--}}
<div class="modal fade" id="modal-detail-terjual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fw-bold" id="dt-title">Detail transaksi</h3>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body" id="dt-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    document.getElementById('modal-detail-terjual').addEventListener('show.bs.modal', async function (e) {
        const btn  = e.relatedTarget;
        const body = document.getElementById('dt-body');

        document.getElementById('dt-title').textContent = 'Detail ' + (btn.dataset.code || 'transaksi');
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
})();
</script>
@endpush
