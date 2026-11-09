{{-- Pemakaian: @include('master-data.partials.delete-modal', ['label' => 'Sales']) --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content ajax-form" method="POST" action="#" novalidate>
            @csrf
            @method('DELETE')

            <div class="modal-header">
                <h5 class="modal-title">Hapus {{ $label }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger d-none form-alert" role="alert"></div>
                <p class="mb-0">
                    Hapus <strong id="deleteName"></strong>? Data yang dihapus tidak bisa dikembalikan.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger" data-loading-text="Menghapus...">Hapus</button>
            </div>
        </form>
    </div>
</div>
