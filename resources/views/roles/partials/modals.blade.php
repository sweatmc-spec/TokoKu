{{--
    Semua modal untuk halaman Role Management (Tambah, Edit, Hapus) + script-nya,
    digabung jadi satu file supaya index.blade.php cukup satu @include.

    Variabel yang dibutuhkan dari pemanggil:
    - $createFail : true kalau modal Tambah harus dibuka ulang otomatis (validasi gagal)
    - $editFail   : true kalau modal Edit harus dibuka ulang otomatis (validasi gagal)
--}}

{{-- ===================== Modal Tambah ===================== --}}
@can('role.create')
<div class="modal fade" id="modalRoleCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-550px">
        <div class="modal-content">
            <form action="{{ route('roles.store') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" name="_form" value="create">

                <div class="modal-header">
                    <h3 class="modal-title">Tambah Role</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Tutup">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>

                <div class="modal-body">
                    @include('roles.partials.form-fields', ['failed' => $createFail])
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-kt-indicator="off">
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress">
                            Memproses...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- ===================== Modal Edit ===================== --}}
@can('role.update')
<div class="modal fade" id="modalRoleEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-550px">
        <div class="modal-content">
            <form action="{{ $editFail ? route('roles.update', old('_edit_id')) : '#' }}" method="POST" autocomplete="off">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_edit_id" value="{{ $editFail ? old('_edit_id') : '' }}">

                <div class="modal-header">
                    <h3 class="modal-title">Edit Role</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Tutup">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>

                <div class="modal-body">
                    @include('roles.partials.form-fields', ['failed' => $editFail])
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-kt-indicator="off">
                        <span class="indicator-label">Simpan Perubahan</span>
                        <span class="indicator-progress">
                            Memproses...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- ===================== Modal Hapus ===================== --}}
@can('role.delete')
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-450px">
        <div class="modal-content">
            <form id="formDelete" method="POST" action="#">
                @csrf
                @method('DELETE')

                <div class="modal-body text-center px-10 pt-10 pb-6">
                    <div class="mb-5">
                        <i class="ki-outline ki-trash fs-3x text-danger"></i>
                    </div>

                    <h3 class="mb-4">Hapus <span class="js-delete-type"></span>?</h3>

                    <div class="text-gray-600 fs-6 mb-2">Kamu akan menghapus:</div>
                    <div class="fw-bold fs-4 text-gray-900 js-delete-name"></div>
                    <div class="text-muted fs-7 js-delete-detail"></div>

                    <div class="alert alert-warning d-none mt-6 mb-0 js-delete-warning"></div>

                    <p class="text-muted fs-7 mt-6 mb-0 js-delete-note">Tindakan ini tidak bisa dibatalkan.</p>
                </div>

                <div class="modal-footer justify-content-center border-0 pt-0 pb-10">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger js-delete-confirm" data-kt-indicator="off">
                        <span class="indicator-label">Ya, Hapus</span>
                        <span class="indicator-progress">
                            Menghapus...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- ===================== Script untuk ketiga modal di atas ===================== --}}
@push('scripts')
<script>
    (function () {
        const createModal = document.getElementById('modalRoleCreate');
        const editModal   = document.getElementById('modalRoleEdit');
        const deleteModal = document.getElementById('modalDelete');

        // --- Modal Tambah: kosongkan form tiap dibuka lewat tombol ---
        if (createModal) createModal.addEventListener('show.bs.modal', function (event) {
            if (!event.relatedTarget) return; // dibuka otomatis setelah validasi gagal → pakai nilai dari server

            const form = createModal.querySelector('form');
            form.querySelector('[name=name]').value = '';
            form.querySelector('[name=description]').value = '';
            createModal.querySelectorAll('.js-error').forEach(function (e) { e.remove(); });
        });

        // --- Modal Edit: isi form dari data baris yang diklik ---
        if (editModal) editModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;

            const form = editModal.querySelector('form');
            form.action = btn.dataset.updateUrl;
            form.querySelector('[name=_edit_id]').value    = btn.dataset.id;
            form.querySelector('[name=name]').value        = btn.dataset.name;
            form.querySelector('[name=description]').value = btn.dataset.description || '';
            editModal.querySelectorAll('.js-error').forEach(function (e) { e.remove(); });
        });

        // --- Modal Hapus: isi nama item + tombol konfirmasi dari data tombol yang diklik ---
        if (deleteModal) deleteModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;

            const setText = function (selector, text) {
                deleteModal.querySelector(selector).textContent = text || '';
            };

            deleteModal.querySelector('form').action = btn.dataset.deleteUrl;
            setText('.js-delete-type', btn.dataset.deleteType);
            setText('.js-delete-name', btn.dataset.deleteName);
            setText('.js-delete-detail', btn.dataset.deleteDetail);

            const blockedReason = btn.dataset.deleteBlocked || '';
            const warning = deleteModal.querySelector('.js-delete-warning');
            warning.textContent = blockedReason;
            warning.classList.toggle('d-none', blockedReason === '');
            deleteModal.querySelector('.js-delete-confirm').disabled = blockedReason !== '';
            deleteModal.querySelector('.js-delete-note').classList.toggle('d-none', blockedReason !== '');
        });

        // --- Buka lagi otomatis modal Tambah/Edit yang tadi disubmit kalau validasi gagal ---
        @if ($createFail || $editFail)
            const reopen = document.getElementById('{{ $createFail ? 'modalRoleCreate' : 'modalRoleEdit' }}');
            if (reopen) bootstrap.Modal.getOrCreateInstance(reopen).show();
        @endif

        // Begitu form disubmit, tombolnya langsung "mati" (disabled) + tampilkan loading,
        // supaya tidak bisa diklik dobel selagi request masih diproses.
        document.querySelectorAll('#modalRoleCreate form, #modalRoleEdit form, #modalDelete form').forEach(function (form) {
            form.addEventListener('submit', function () {
                const btn = form.querySelector('button[type=submit]');
                if (btn && !btn.disabled) {
                    btn.disabled = true;
                    btn.setAttribute('data-kt-indicator', 'on');
                }
            });
        });
    })();
</script>
@endpush